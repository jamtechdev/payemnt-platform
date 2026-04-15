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
    public function showLogin(): Response
    {
        return Inertia::render('auth/login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            if ($user) {
                $user->incrementLoginAttempts();
            }

            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        $request->session()->regenerate();
        /** @var User $loggedUser */
        $loggedUser = Auth::user();
        $loggedUser->resetLoginAttempts();
        $loggedUser->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('login', null, [], ['event' => 'login'], $loggedUser);

        if ($loggedUser->hasRole('customer_service')) {
            return redirect()->route('admin.cs.dashboard');
        }
        if ($loggedUser->hasRole('reconciliation_admin')) {
            return redirect()->route('admin.reports.dashboard');
        }
        if ($loggedUser->hasRole('super_admin')) {
            return redirect()->route('admin.platform.dashboard');
        }
        if ($loggedUser->hasRole('admin')) {
            return redirect()->route('admin.platform.dashboard');
        }

        return redirect()->route('login')->with('error', 'No valid role assigned.');
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
