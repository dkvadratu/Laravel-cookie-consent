<?php

namespace Dkvadratu\CookieConsent\Filament\Resources\Pages;

use Dkvadratu\CookieConsent\Filament\Resources\CookieConsentLogResource;
use Filament\Resources\Pages\ListRecords;

class ListCookieConsentLogs extends ListRecords
{
    protected static string $resource = CookieConsentLogResource::class;
}
