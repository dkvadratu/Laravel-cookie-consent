<?php

namespace Dkvadratu\CookieConsent;

use Dkvadratu\CookieConsent\Console\InstallCommand;
use Dkvadratu\CookieConsent\Http\LogCookieConsent;
use Illuminate\Support\ServiceProvider;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;

class CookieConsentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dkvadratu-cookie-consent.php', 'dkvadratu-cookie-consent');

        $this->app->booting(function (): void {
            $middleware = (array) config('cookieconsent.url.middleware', []);
            if (! in_array('web', $middleware, true)) {
                array_unshift($middleware, 'web');
            }
            if (! in_array(LogCookieConsent::class, $middleware, true)) {
                $middleware[] = LogCookieConsent::class;
            }
            config(['cookieconsent.url.middleware' => $middleware]);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/dkvadratu-cookie-consent.php' => config_path('dkvadratu-cookie-consent.php')], 'dkvadratu-cookie-consent-config');

        $this->app->booted(function (): void {
            $overrides = collect(config('view.paths', []))
                ->map(fn (string $path): string => $path.'/vendor/cookie-consent')
                ->filter(fn (string $path): bool => is_dir($path))
                ->all();
            $this->app['view']->prependNamespace('cookie-consent', [...$overrides, __DIR__.'/../resources/views']);

            $cookies = app(CookiesRegistrar::class);
            $cookies->essentials()->session()->csrf();
            $cookies->essentials()->name('cookie_consent_id')
                ->description('Identifikatorius sutikimų pasirinkimų istorijai saugoti.')
                ->duration((int) config('cookieconsent.cookie.duration'));

            $id = config('dkvadratu-cookie-consent.analytics_id');
            if (config('dkvadratu-cookie-consent.enable_analytics') && is_string($id) && preg_match('/^G-[A-Z0-9]+$/', $id)) {
                $cookies->analytics()->google($id);
            }
        });
    }
}
