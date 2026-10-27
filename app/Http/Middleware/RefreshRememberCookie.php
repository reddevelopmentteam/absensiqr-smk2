<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RefreshRememberCookie
{
    /**
     * The interval, in seconds, between remember-cookie extensions.
     */
    private const REFRESH_INTERVAL = 60 * 60 * 24;

    /**
     * The session key that stores the last extension timestamp.
     */
    private const SESSION_KEY = 'auth.remember_cookie_refreshed_at';

    /**
     * Extend the "remember me" cookie while the user keeps using the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $guard = Auth::guard();

        // Only session-based guards issue a recaller ("remember me") cookie.
        if (! $guard instanceof SessionGuard || ! $guard->check()) {
            return $response;
        }

        $cookieName = $guard->getRecallerName();

        // Users who did not opt into "remember me" keep a session-only login.
        if (! $request->hasCookie($cookieName)) {
            return $response;
        }

        // A login during this request already queued a fresh cookie.
        if (Cookie::hasQueued($cookieName)) {
            return $response;
        }

        $recaller = $request->cookie($cookieName);
        $user = $guard->user();

        if (! is_string($recaller) || $recaller === '') {
            // The cookie exists but could not be decrypted or is empty.
            Cookie::forget($cookieName);

            return $response;
        }

        if (! $user) {
            return $response;
        }

        $segments = explode('|', $recaller);
        $rememberToken = (string) $user->getRememberToken();

        // Never keep alive a recaller that belongs to another account or was
        // issued for a token that has since been rotated (for example by a
        // logout on another device). The browser may still hold an old cookie
        // after a different user logs in without "remember me".
        if (count($segments) !== 3
            || $segments[0] !== (string) $user->getAuthIdentifier()
            || $rememberToken === ''
            || ! hash_equals($rememberToken, $segments[1])) {
            Cookie::forget($cookieName);

            return $response;
        }

        $lastRefreshedAt = (int) $request->session()->get(self::SESSION_KEY, 0);

        // Livewire and the scanner ping poll often; do not send a Set-Cookie
        // header on every request just to extend the same expiry.
        if ($lastRefreshedAt > 0 && (now()->timestamp - $lastRefreshedAt) < self::REFRESH_INTERVAL) {
            return $response;
        }

        // Reuse the existing recaller value and only extend its expiry. This
        // keeps other devices' remember tokens valid; rotating the token here
        // would log them out.
        $minutes = (int) config(
            'auth.guards.'.Auth::getDefaultDriver().'.remember',
            576000,
        );

        Cookie::queue($cookieName, $recaller, $minutes);

        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        return $response;
    }
}
