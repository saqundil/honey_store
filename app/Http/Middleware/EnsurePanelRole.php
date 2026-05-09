<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $currentRole = null;

        if (Auth::guard('web')->check()) {
            $currentRole = Auth::guard('web')->user()?->role;
        } elseif (Auth::guard('seller')->check()) {
            $currentRole = 'seller';
        }

        abort_unless($currentRole !== null && in_array($currentRole, $roles, true), 403);

        return $next($request);
    }
}