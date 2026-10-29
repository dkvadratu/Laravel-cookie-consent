<?php

namespace Dkvadratu\CookieConsent\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'cookie-consent:install
        {--analytics-id= : Optional GA4 measurement ID}
        {--migrate : Run this package’s migrations}
        {--force : Allow migrations in production without a prompt}';

    protected $description = 'Configure cookie consent and optionally run its migrations';

    public function handle(): int
    {
        $id = $this->option('analytics-id');
        if ($id === null && $this->input->isInteractive()) {
            $id = $this->ask('GA4 measurement ID (leave blank to keep the current setting)');
        }

        if (is_string($id)) {
            $id = strtoupper(trim($id));
        }
        if ($id !== null && $id !== '' && (! is_string($id) || ! preg_match('/^G-[A-Z0-9]+$/', $id))) {
            $this->error('The GA4 measurement ID must have the format G-XXXXXXXXXX.');

            return self::FAILURE;
        }

        $runMigrations = (bool) $this->option('migrate');
        if (! $runMigrations && $this->input->isInteractive()) {
            $runMigrations = $this->confirm('Run the cookie consent migrations now?', false);
        }
        if ($runMigrations && $this->laravel->isProduction() && ! $this->input->isInteractive() && ! $this->option('force')) {
            $this->error('In production, non-interactive migrations require --force.');

            return self::FAILURE;
        }

        if ($id !== null && $id !== '' && ! $this->setAnalyticsId($id)) {
            return self::FAILURE;
        }

        $configPath = config_path('dkvadratu-cookie-consent.php');
        if (! File::exists($configPath)) {
            if (! File::copy(__DIR__.'/../../config/dkvadratu-cookie-consent.php', $configPath)) {
                $this->error('Unable to publish the cookie consent configuration.');

                return self::FAILURE;
            }
            $this->info('Published the cookie consent configuration.');
        } else {
            $this->info('Existing cookie consent configuration was preserved.');
        }

        if ($this->call('config:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($runMigrations) {
            $options = [
                '--path' => realpath(__DIR__.'/../../database/migrations'),
                '--realpath' => true,
            ];
            if ($this->option('force')) {
                $options['--force'] = true;
            }
            if ($this->call('migrate', $options) !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->warn('Migrations were not run. Run php artisan migrate before accepting consent.');
        }

        $this->info('Add @cookieconsentscripts and @cookieconsentview to your Blade layout.');
        $this->info('For the optional Filament 5 table, register the plugin and authorization gate; see the README.');

        return self::SUCCESS;
    }

    private function setAnalyticsId(string $id): bool
    {
        $path = $this->laravel->environmentFilePath();
        if (! File::exists($path)) {
            $this->error('The environment file does not exist. Create it before setting a GA4 ID.');

            return false;
        }

        $contents = File::get($path);
        $pattern = '/^[ \t]*GOOGLE_ANALYTICS_ID[ \t]*=[ \t]*([^\r\n]*)/m';
        if (preg_match($pattern, $contents, $matches)) {
            $current = trim($matches[1], " \t\"'");
            if ($current !== '' && $current !== $id) {
                $this->error('GOOGLE_ANALYTICS_ID already has a different value. Update it manually if needed.');

                return false;
            }
            if ($current === $id) {
                return true;
            }
            $contents = preg_replace($pattern, 'GOOGLE_ANALYTICS_ID='.$id, $contents, 1);
        } else {
            $contents = rtrim($contents, "\r\n").PHP_EOL.'GOOGLE_ANALYTICS_ID='.$id.PHP_EOL;
        }

        File::put($path, $contents);
        $this->info('Saved the GA4 measurement ID in the environment file.');

        return true;
    }
}
