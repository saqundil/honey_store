<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            abort_unless(Auth::guard('web')->user()?->isAdmin(), 403);

            Auth::shouldUse('web');

            return $next($request);
        }

        if (Auth::guard('seller')->check()) {
            Auth::shouldUse('seller');

            return $next($request);
        }

        return redirect()->route('admin.login');
    }
}