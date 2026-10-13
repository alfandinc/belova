<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RefreshActiveUserEmotion
{
    /** Users count as online for 15 minutes, so refreshing the shared entry more often than this is wasted work. */
    private const REFRESH_AFTER_SECONDS = 60;

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user) {
            $emotion = $request->session()->get('user_emotion', AuthController::DEFAULT_EMOTION);
            $entry = Cache::get('active_user_emotions', [])[$user->id] ?? null;

            $isFresh = $entry
                && ($entry['emotion'] ?? null) === $emotion
                && !empty($entry['updated_at'])
                && Carbon::parse($entry['updated_at'])->gt(now()->subSeconds(self::REFRESH_AFTER_SECONDS));

            if (!$isFresh) {
                AuthController::storeUserEmotion($user, $emotion);
            }
        }

        return $next($request);
    }
}
