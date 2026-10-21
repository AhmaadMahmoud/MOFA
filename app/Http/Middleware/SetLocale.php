<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the {locale} route prefix, makes route() fill it in by default,
 * and remembers the choice for the next visit to "/".
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! Locales::isSupported($locale)) {
            abort(404);
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Keep {locale} out of controller arguments.
        $request->route()->forgetParameter('locale');

        if ($request->cookie(Locales::COOKIE) !== $locale) {
            Cookie::queue(Locales::COOKIE, $locale, 60 * 24 * 365);
        }

        return $next($request);
    }
}
