<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Explicit ?locale=ar on the request (e.g. from the lang switcher).
        $fromQuery = $request->query('locale');
        if (is_string($fromQuery) && in_array($fromQuery, self::SUPPORTED, true)) {
            return $fromQuery;
        }

        // 2. Logged-in user's preference.
        $user = $request->user();
        if ($user && in_array($user->locale, self::SUPPORTED, true)) {
            return $user->locale;
        }

        // 3. Session.
        $fromSession = session('locale');
        if (is_string($fromSession) && in_array($fromSession, self::SUPPORTED, true)) {
            return $fromSession;
        }

        // 4. Default.
        return config('app.locale', 'ar');
    }
}
