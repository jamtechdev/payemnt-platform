<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lastActivity = (int) $request->session()->get('last_activity', time());
        $maxIdleSeconds = max(60, (int) config('session.lifetime', 30) * 60);
        if (time() - $lastActivity > $maxIdleSeconds) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Session expired due to inactivity.');
        }

        $request->session()->put('last_activity', time());

        return $next($request);
    }
}
