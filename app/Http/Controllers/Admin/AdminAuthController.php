<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::guard('web')->user();

        if (Auth::guard('web')->check() && $admin?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('seller')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $guard = $validated['account_type'] === 'seller' ? 'seller' : 'web';
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if ($guard === 'web') {
            $credentials['role'] = 'admin';
        }

        if (! Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => app()->isLocale('ar')
                        ? 'بيانات تسجيل الدخول غير صحيحة.'
                        : 'The provided credentials are incorrect.',
                ])
                ->onlyInput('email', 'account_type');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::guard('seller')->check()) {
            Auth::guard('seller')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}