<?php

namespace Dkvadratu\CookieConsent\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Whitecube\LaravelCookieConsent\Facades\Cookies;

class LogCookieConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $actions = [
            'cookieconsent.accept.all' => 'accept_all',
            'cookieconsent.accept.essentials' => 'reject_all',
            'cookieconsent.accept.configuration' => 'custom',
            'cookieconsent.reset' => 'withdraw',
        ];
        $action = $actions[$request->route()?->getName()] ?? null;

        if (! $request->isMethod('POST') || ! $action || $response->getStatusCode() >= 400) {
            return $response;
        }

        $categories = [];
        if ($action !== 'withdraw') {
            $preferenceCookie = collect($response->headers->getCookies())
                ->first(fn ($cookie) => $cookie->getName() === config('cookieconsent.cookie.name'));
            $preferences = json_decode($preferenceCookie?->getValue() ?? '', true);

            if (! is_array($preferences) || ! isset($preferences['consent_at'])) {
                return $response;
            }

            foreach (Cookies::getCategories() as $category) {
                $cookieNames = array_map(fn ($cookie) => $cookie->name, $category->getCookies());
                if ($cookieNames !== [] && collect($cookieNames)->every(fn ($name) => ($preferences[$name] ?? false) === true)) {
                    $categories[] = $category->key();
                }
            }
        }

        $cookieId = $request->cookie('cookie_consent_id');
        if (! is_string($cookieId) || ! Str::isUuid($cookieId)) {
            $cookieId = (string) Str::uuid();
        }

        DB::table('cookie_consent_logs')->insert([
            'cookie_id' => $cookieId,
            'consented_categories' => json_encode($categories, JSON_THROW_ON_ERROR),
            'action' => $action,
            'ip_address' => $this->maskIpAddress($request->ip()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response->headers->setCookie(cookie(
            name: 'cookie_consent_id', value: $cookieId,
            minutes: (int) config('cookieconsent.cookie.duration'),
            domain: config('cookieconsent.cookie.domain'),
            secure: $request->isSecure(), httpOnly: true, sameSite: 'lax',
        ));

        return $response;
    }

    private function maskIpAddress(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $keep = strlen($packed) === 4 ? 2 : 4;
        $masked = inet_ntop(substr($packed, 0, $keep).str_repeat("\0", strlen($packed) - $keep));

        return $masked === false ? null : $masked;
    }
}
