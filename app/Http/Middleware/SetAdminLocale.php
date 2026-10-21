<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard is in English unless the admin switched it to Arabic from the user menu.
 * Independent from the public site's language.
 */
class SetAdminLocale
{
    public const SESSION_KEY = 'admin_locale';

    public const LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY, 'en');

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'en');

        return $next($request);
    }
}
