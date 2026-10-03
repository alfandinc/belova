<?php

namespace App\Http\Middleware;

use App\Models\ERM\Visitation;
use App\Models\Finance\Invoice;
use Closure;
use Illuminate\Http\Request;

/**
 * Event billing endpoints are open to every logged-in user, so they may only touch event visits
 * (jenis_kunjungan 4). The visit comes from the {visitation_id} route parameter, the visitation_id
 * input, or the visit of the {invoice} route parameter.
 */
class EnsureEventVisitation
{
    public function handle(Request $request, Closure $next)
    {
        $visitationId = $request->route('visitation_id') ?? $request->input('visitation_id');

        if ($visitationId === null && $request->route('invoice') !== null) {
            $visitationId = Invoice::whereKey($request->route('invoice'))->value('visitation_id');
        }

        $isEventVisit = $visitationId !== null && Visitation::whereKey($visitationId)
            ->where('jenis_kunjungan', 4)
            ->exists();

        if (!$isEventVisit) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Hanya untuk kunjungan event.'], 403);
            }

            abort(403, 'Hanya untuk kunjungan event.');
        }

        return $next($request);
    }
}
