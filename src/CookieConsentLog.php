<?php

namespace Dkvadratu\CookieConsent;

use Illuminate\Database\Eloquent\Model;

class CookieConsentLog extends Model
{
    protected $table = 'cookie_consent_logs';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['consented_categories' => 'array'];
    }
}
