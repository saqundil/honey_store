<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = config('app.available_locales', ['en', 'ar']);
        $routeLocale = $request->route('locale');
        $locale = is_string($routeLocale)
            ? $routeLocale
            : session('locale', config('app.locale'));

        if (! in_array($locale, $availableLocales, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
