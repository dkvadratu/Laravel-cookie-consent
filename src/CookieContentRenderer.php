<?php

namespace Dkvadratu\CookieConsent;

use Whitecube\LaravelCookieConsent\CookiesManager;

class CookieContentRenderer
{
    public function __construct(private CookiesManager $cookies) {}

    public function render(string $html, ?string $buttonLabel = null): string
    {
        if (str_contains($html, '@cookieconsentinfo')) {
            $html = $this->unwrapMarkerParagraph($html, '@cookieconsentinfo');
            $html = $this->cookies->replaceInfoTag($html);
        }

        if (str_contains($html, '@cookieconsentbutton')) {
            $html = $this->unwrapMarkerParagraph($html, '@cookieconsentbutton');
            $button = $this->cookies->renderButton('reset', $buttonLabel ?? __('cookieConsent::cookies.manage'), [
                'class' => 'cookie-settings',
            ]);
            $html = str_replace('@cookieconsentbutton', $button, $html);
        }

        return $html;
    }

    private function unwrapMarkerParagraph(string $html, string $marker): string
    {
        $pattern = '/<p(?:\\s[^>]*)?>(?:\\s|&nbsp;|\\x{00a0})*'.preg_quote($marker, '/').'(?:\\s|&nbsp;|\\x{00a0})*<\\/p>/u';

        return preg_replace_callback($pattern, fn (): string => $marker, $html) ?? $html;
    }
}
