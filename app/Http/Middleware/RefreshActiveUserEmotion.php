<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefreshActiveUserEmotion
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user) {
            $emotion = $request->session()->get('user_emotion', AuthController::DEFAULT_EMOTION);
            AuthController::storeUserEmotion($user, $emotion);
        }

        return $next($request);
    }
}