<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Recaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Is the browser still logged in? Polled by partials.session_watch to show a popup once the user is logged out
 * (session expired, logged out in another tab / device, account set nonaktif, sessions cleared by a deploy).
 *
 * The route runs without StartSession, so asking does not keep the session alive: the cookie is checked against
 * the sessions table directly. A browser with a valid "Ingat saya" cookie still counts as logged in, because its
 * next request logs it back in on its own.
 */
class SessionStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        $row = null;
        $sessionId = $request->cookie(config('session.cookie'));
        if (is_string($sessionId) && strlen($sessionId) === 40 && ctype_alnum($sessionId)) {
            $row = DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->first(['user_id', 'last_activity']);
        }

        $expired = $row && $row->last_activity < now()->getTimestamp() - config('session.lifetime') * 60;
        $userId = $row && !$expired ? (int) $row->user_id : 0;

        if (!$userId) {
            $userId = $this->rememberedUserId($request);
        }

        if ($userId) {
            $user = User::find($userId, ['id', 'is_active']);
            if ($user && $user->is_active) {
                return $this->respond(['active' => true, 'user_id' => $user->id]);
            }
            if ($user) {
                return $this->respond(['active' => false, 'reason' => 'nonaktif']);
            }
        }

        return $this->respond(['active' => false, 'reason' => $expired ? 'expired' : 'logged_out']);
    }

    private function rememberedUserId(Request $request): int
    {
        $guard = Auth::guard('web');
        $cookie = $request->cookie($guard->getRecallerName());
        if (!is_string($cookie)) {
            return 0;
        }

        $recaller = new Recaller($cookie);
        if (!$recaller->valid()) {
            return 0;
        }

        $user = $guard->getProvider()->retrieveByToken($recaller->id(), $recaller->token());

        return $user ? (int) $user->getAuthIdentifier() : 0;
    }

    private function respond(array $data)
    {
        return response()->json($data)->header('Cache-Control', 'no-store');
    }
}
