<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Logs out a user whose account was set to nonaktif while they were logged in
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda dinonaktifkan.'], 401);
            }

            return redirect('/')->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi Admin.']);
        }

        return $next($request);
    }
}
