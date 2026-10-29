<?php

namespace Dkvadratu\CookieConsent\Filament;

use Dkvadratu\CookieConsent\Filament\Resources\CookieConsentLogResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class CookieConsentPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'cookie-consent-logs';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CookieConsentLogResource::class]);
    }

    public function boot(Panel $panel): void {}
}
