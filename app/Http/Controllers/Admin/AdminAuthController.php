<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Only these staff roles can access admin panel login.
     *
     * @var list<string>
     */
    private const ALLOWED_LOGIN_ROLES = ['admin', 'super_admin'];

    public function showLogin(): Response
    {
        return Inertia::render('auth/login');
    }

    // public function login(LoginRequest $request): RedirectResponse
    // {
    //     $credentials = $request->validated();
    //     $user = User::query()->where('email', $credentials['email'])->first();

    //     if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
    //         if ($user) {
    //             $user->incrementLoginAttempts();
    //         }

    //         return back()->withErrors(['email' => 'Invalid credentials.']);
    //     }

    //     $request->session()->regenerate();
    //     /** @var User $loggedUser */
    //     $loggedUser = Auth::user();
    //     $loggedUser->resetLoginAttempts();
    //     $loggedUser->forceFill(['last_login_at' => now()])->save();
    //     AuditLog::record('login', null, [], ['event' => 'login'], $loggedUser);

    //     if ($loggedUser->hasRole('customer_service')) {
    //         return redirect()->route('admin.cs.dashboard');
    //     }
    //     if ($loggedUser->hasRole('reconciliation_admin')) {
    //         return redirect()->route('admin.reports.dashboard');
    //     }
    //     if ($loggedUser->hasRole('super_admin')) {
    //         return redirect()->route('admin.platform.dashboard');
    //     }
    //     if ($loggedUser->hasRole('admin')) {
    //         return redirect()->route('admin.platform.dashboard');
    //     }

    //     return redirect()->route('login')->with('error', 'No valid role assigned.');
    // }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->hasAnyRole(self::ALLOWED_LOGIN_ROLES)) {
            return back()->withErrors([
                'email' => 'You are not allowed to access admin panel.',
            ]);
        }

        if (! Auth::attempt($credentials)) {
            if ($user) {
                $user->incrementLoginAttempts();
            }

            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $loggedUser */
        $loggedUser = Auth::user();

        $loggedUser->resetLoginAttempts();
        $loggedUser->forceFill(['last_login_at' => now()])->save();

        AuditLog::record('login', null, [], ['event' => 'login'], $loggedUser);

        if ($loggedUser->hasAnyRole(self::ALLOWED_LOGIN_ROLES)) {
            return redirect()->route('admin.platform.dashboard');
        }

        Auth::logout();

        return redirect()->route('login')->with('error', 'Unauthorized access.');
    }

    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            AuditLog::record('logout', null, [], ['event' => 'logout'], $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
