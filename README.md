# Dkvadratu Laravel Cookie Consent

A Laravel package built on [Whitecube Laravel Cookie Consent](https://github.com/whitecube/laravel-cookie-consent). It provides consent management, a consent history with masked IP addresses, optional GA4 integration, and an optional read-only Filament 5 table.

## Installation

### Requirements

- PHP 8.2 or later and Laravel 12 or 13.
- Filament 5 is required only for the optional admin table.
- A database connection for the consent history.

### 1. Add the GitHub repository

Until the package is available on Packagist, add its GitHub repository to the **consuming Laravel application's** Composer configuration:

```bash
composer config repositories.dkvadratu-cookie-consent vcs https://github.com/dkvadratu/laravel-cookie-consent.git
```

The URL above assumes the repository is named `dkvadratu/laravel-cookie-consent`. Replace it if your repository has another name. If the repository is private, configure Composer's GitHub authentication or SSH access separately; do not commit credentials.

### 2. Install the package

```bash
composer require 'dkvadratu/laravel-cookie-consent:^1.0'
```

A matching `1.x` release tag, such as `v1.0.0`, must exist in the repository. Composer installs `whitecube/laravel-cookie-consent` automatically if it is missing, and resolves it with your existing dependencies. Laravel discovers both providers automatically unless package discovery is disabled.

### 3. Run the setup wizard

```bash
php artisan cookie-consent:install
```

The wizard asks for an optional GA4 measurement ID and whether to run this package's migrations. It publishes `config/dkvadratu-cookie-consent.php` if it does not already exist and clears Laravel's configuration cache. A supplied GA4 ID is stored as `GOOGLE_ANALYTICS_ID` in the existing environment file. An existing, different ID is not overwritten; update it manually if necessary.

In production, the migration command uses Laravel's normal confirmation unless `--force` is explicitly provided. Review your database and existing schema before migrating.

For a non-interactive installation:

```bash
php artisan cookie-consent:install --analytics-id=G-XXXXXXXXXX --migrate --no-interaction
```

The ID and `--migrate` are optional. Without `--migrate`, non-interactive setup leaves database changes for a later `php artisan migrate` command. For non-interactive **production** migrations, add `--force` after reviewing the deployment:

```bash
php artisan cookie-consent:install --migrate --force --no-interaction
```

The installer runs only this package's migrations when asked. It does not automatically edit your layout, install Filament, configure your authorization gate, or register third-party trackers such as Meta Pixel. Complete the integration steps below.

### 4. Existing projects and deployment

If `cookie_consent_logs` already exists, the migrations retain it and add `ip_address` if missing. Check that the existing table contains `cookie_id`, `consented_categories`, `action`, `created_at`, and `updated_at`. Add missing columns using a new migration; do not delete previously executed migrations or existing records.

If the application already registers its own consent logging middleware or GA4 integration, remove the duplicate implementation after reviewing it. Otherwise, events or tracking scripts may be recorded or loaded twice.

After configuration and migrations, rebuild application caches if your deployment uses them:

```bash
php artisan optimize:clear
php artisan optimize
```

## Usage

### Consent banner

Add Whitecube's directives to your main Blade layout:

```blade
<head>
    {{-- Other head content --}}
    @cookieconsentscripts
</head>
<body>
    {{-- Page content --}}
    @cookieconsentview
</body>
```

The package uses Whitecube's default banner. To customize its appearance or translations, see the [Whitecube customization guide](https://github.com/whitecube/laravel-cookie-consent#customization). Include a link to your privacy or cookie policy and give visitors a way to change their choice:

```blade
@cookieconsentbutton(action: 'reset', label: 'Cookie settings')
```

Reset withdraws the previous preference, records the action, and displays the notice again.

### Google Analytics

Set `GOOGLE_ANALYTICS_ID` in the environment, either through the wizard or manually. A valid GA4 ID starts with `G-`; when present and enabled, Whitecube loads Analytics after the analytics category is accepted. To manage Analytics elsewhere, set `enable_analytics` to `false` in `config/dkvadratu-cookie-consent.php`.

Remove any unconditional Google or other non-essential tracking code from your layout. Register other tracking services in appropriate Whitecube consent categories yourself; this package does not register Meta Pixel.

### Adding tracking scripts: Meta Pixel, GTM, Clarity, and Hotjar

Register extra services in the **application using this package**, not inside `vendor/` and not in this package's source. The examples below use Whitecube's `analytics` category for analytics and `optional` for marketing. The default name of the latter is “Optional”; customize its title and description if you present it as “Marketing”. Check the purpose of each service and its actual cookies before choosing a category.

1. Publish the Whitecube application provider if you do not already have one:

   ```bash
   php artisan vendor:publish --tag=laravel-cookie-consent-service-provider --no-interaction
   ```

2. Register `App\Providers\CookiesServiceProvider::class` in `bootstrap/providers.php` if it is not already registered. Keep the provider supplied by this package enabled as well.
3. Create one Blade file per tracker under `resources/views/tracking/`. Put the provider's **single `<script>...</script>` tag** in that file. Obtain the current snippet and ID from the service's dashboard. Do not place the snippet directly in the main layout: that would load it without category consent.
4. Add the relevant entries to your application's `app/Providers/CookiesServiceProvider.php`:

   ```php
   <?php

   namespace App\Providers;

   use Whitecube\LaravelCookieConsent\Consent;
   use Whitecube\LaravelCookieConsent\CookiesServiceProvider as ServiceProvider;
   use Whitecube\LaravelCookieConsent\Facades\Cookies;

   class CookiesServiceProvider extends ServiceProvider
   {
       protected function registerCookies(): void
       {
           // Meta Pixel: marketing. Create resources/views/tracking/meta-pixel.blade.php.
           Cookies::optional()->name('_fbp')
               ->description('Used by Meta Pixel for advertising measurement.')
               ->duration(90 * 24 * 60)
               ->accepted(fn (Consent $consent) => $consent->script(
                   view('tracking.meta-pixel')->render()
               ));

           // Microsoft Clarity: analytics. Create resources/views/tracking/clarity.blade.php.
           Cookies::analytics()->name('_clck')
               ->description('Used by Microsoft Clarity to recognize a visitor.')
               ->duration(365 * 24 * 60)
               ->accepted(fn (Consent $consent) => $consent->script(
                   view('tracking.clarity')->render()
               ));
       }
   }
   ```

   Enable only services you actually use. Durations are in **minutes**; verify the exact cookie names, purposes, and lifetimes against the deployed vendor configuration and register all additional cookies used. The package already registers Laravel's session and CSRF cookies, its own consent identifier, and GA4 cookies when GA4 is enabled. Do not register those again.

5. Clear cached configuration or views if applicable, then test in a fresh browser session. Before consent, the relevant vendor's network requests must be absent. Accept analytics alone and marketing alone separately; verify each script is loaded only for its category. Refresh and reset consent, then repeat. Clearing configuration is available with `php artisan optimize:clear`.

#### Where to obtain each script

- **Meta Pixel:** in Meta Events Manager, open your pixel and obtain the base code for manual installation. Put its JavaScript `<script>` in `tracking/meta-pixel.blade.php` and register it under `optional` as above. The example does not add the separate `<noscript>` tracking pixel: do not add that image unconditionally, as it would send a request without marketing consent.
- **Microsoft Clarity:** in the Clarity project, choose **Settings → Setup → Get tracking code**, put the script in `tracking/clarity.blade.php`, and register it under `analytics` as above. See [Microsoft's setup guide](https://learn.microsoft.com/en-us/clarity/setup-and-installation/clarity-setup).
- **Hotjar:** obtain the installation script and site ID from your Hotjar dashboard and save the script as `tracking/hotjar.blade.php`. Register a real Hotjar cookie in `Cookies::analytics()` with its actual name and duration, using the same `accepted()` pattern as the Clarity example. Confirm the vendor's current cookie list and whether session recording is appropriate for your site.
- **Google Tag Manager:** obtain your web container snippet from your GTM workspace; the container ID starts with `GTM-`, whereas a GA4 measurement ID starts with `G-`. Save the container's JavaScript `<script>` as `tracking/gtm.blade.php` and register it with the category appropriate to the tags that container loads. Follow [Google's container setup guide](https://support.google.com/tagmanager/answer/14847097). GTM is a container, not a consent category: if it contains both analytics and marketing tags, gate each tag with the relevant consent in GTM or separate the integrations. Loading the entire mixed-purpose container after only one category is accepted is not sufficient. Do not add its `<noscript>` iframe unconditionally.

Each `accepted()` callback must return a valid script tag. Whitecube adds the script after acceptance and on later page loads with that consent; it does not turn arbitrary HTML, multiple sibling script tags, or a `<noscript>` block into independently controlled scripts when consent is accepted through JavaScript. Split multiple scripts into separate registered callbacks where needed. Check vendor-specific consent settings and test actual browser requests, including after consent withdrawal. A full page reload after withdrawal is needed to stop scripts already running on that page; previously set third-party cookies may require separate cleanup.

#### Essential scripts

Use `Cookies::essentials()` only for cookies strictly required for the requested service. Essential category acceptance cannot be declined. First-party code that does not need optional trackers can remain in your application's normal asset pipeline; do not classify analytics, advertising, session recording, or a mixed-purpose GTM container as essential merely to make it load sooner.

For custom categories, translated labels, multiple cookies per vendor, consent callbacks, and more detailed configuration, see [Whitecube's cookie registration guide](https://github.com/whitecube/laravel-cookie-consent#registering-cookies).

### Cookie list in a privacy policy

Render the registered categories and cookies in a Blade view:

```blade
@cookieconsentinfo
```

For content stored in an editor, replace the marker after rendering the content:

```php
use Whitecube\LaravelCookieConsent\Facades\Cookies;

$html = Cookies::replaceInfoTag($renderedContent);
```

Place `@cookieconsentinfo` in a separate editor paragraph. The generated list contains cookies registered with Whitecube; register the other cookies your application uses.

### Optional Filament table

With Filament 5 installed, add the plugin to your panel provider:

```php
use Dkvadratu\CookieConsent\Filament\CookieConsentPlugin;

// In your Panel configuration:
->plugins([
    CookieConsentPlugin::make(),
])
```

Define the gate in `AppServiceProvider::boot()` or another authorization provider, using your application's actual administrator authorization:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('view-cookie-consent-logs', fn (User $user): bool => $user->is_admin);
```

This example assumes `is_admin` is a trusted field in your user model. If the gate is not defined or denies access, the table is unavailable. The table is read-only.

## Details

- **Events:** accepting all, accepting essentials, a custom selection, and withdrawing a previous choice.
- **Stored data:** a random consent identifier, accepted categories, action, timestamps, and a masked IP address. The user agent is not stored.
- **IP masking:** IPv4 addresses retain the first two octets; IPv6 addresses retain the first 32 bits. Masking happens before the database insert. A masked IP and identifier may still be linkable, so configure access controls and an appropriate retention policy.
- **Proxies:** the IP comes from Laravel's request. Configure trusted proxies correctly if the application runs behind a proxy or CDN.
- **Cookie:** `cookie_consent_id` links consent events, is registered as essential, and uses Whitecube's configured duration. The package attaches Laravel's `web` middleware and its logging middleware to Whitecube routes.
- **Rollback:** package migrations deliberately retain the table and IP column, because either may have existed before installation. Automatic log pruning is not provided.
- **Production verification:** in a browser without previous consent, test all choices, withdrawal, log entries, and ensure non-essential network requests do not run without the relevant consent.

### License

The package currently declares `proprietary` in its Composer metadata. A public GitHub repository alone does not grant an open-source license; the owner should select and add a license before offering broad reuse.
