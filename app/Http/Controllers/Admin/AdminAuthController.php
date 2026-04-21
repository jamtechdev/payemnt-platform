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
    private const ALLOWED_LOGIN_ROLES = ['admin', 'super_admin', 'customer_service', 'reconciliation_admin'];

    public function showLogin(): Response
    {
        return Inertia::render('auth/login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->hasAnyRole(self::ALLOWED_LOGIN_ROLES)) {
            if ($user) {
                AuditLog::record('login_failed', $user, [], ['reason' => 'unauthorized_role'], null);
            } else {
                AuditLog::record('login_failed', null, [], ['email' => $credentials['email'], 'reason' => 'unknown_user'], null);
            }

            return back()->withErrors([
                'email' => 'You are not allowed to access admin panel.',
            ]);
        }

        // AUTH-005: Check account lockout before attempting login
        if ($user->isLocked()) {
            return back()->withErrors([
                'email' => 'Account is locked due to too many failed attempts. Please try again later.',
            ]);
        }

        if (! Auth::attempt($credentials)) {
            $user->incrementLoginAttempts();
            AuditLog::record('login_failed', $user, [], ['reason' => 'invalid_credentials'], null);

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

        // Role-based redirect (BRD Section 6.2)
        if ($loggedUser->hasRole('customer_service')) {
            return redirect()->route('admin.cs.dashboard');
        }
        if ($loggedUser->hasRole('reconciliation_admin')) {
            return redirect()->route('admin.reports.dashboard');
        }

        return redirect()->route('admin.platform.dashboard');
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
