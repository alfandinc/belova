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
            return redirect('/'); // Redirect to main menue
        }

        if (!Auth::user()->hasAnyRole($role)) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}
