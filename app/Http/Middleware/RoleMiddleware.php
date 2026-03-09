<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::check()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect('/'); // Redirect to main menu
        }

        if (!Auth::user()->hasAnyRole($role)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}
