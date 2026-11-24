<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale chosen in the panel language switcher (kept in the
 * session) to every web request. Falls back to the configured default and
 * ignores any locale not offered by the application.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('panel.locales', []));
        $locale = $request->session()->get('locale');

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = config('panel.default_locale', config('app.locale'));
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
