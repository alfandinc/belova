<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabPermintaan;
use Illuminate\Support\Facades\Auth;

class LabNotificationController extends Controller
{
    /**
     * Return count and latest completed lab requests for the logged in doctor's patients
     * since a given timestamp (poll incremental) or last 5 if none provided.
     */
    public function completed(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('Dokter')) {
            return response()->json(['ok'=>false,'message'=>'Unauthorized'], 403);
        }

        $since = $request->query('since'); // ISO string or Y-m-d H:i:s
        $query = LabPermintaan::with(['visitation.pasien','labTest'])
            ->where('status','completed')
            ->whereHas('visitation', function($q) use ($user){
                $q->where('dokter_id', $user->dokter->id ?? 0);
            });

        if ($since) {
            // Incremental: order ascending so we show earliest first
            $query->where('completed_at','>', $since)->orderBy('completed_at','asc');
        } else {
            // Initial load: no notifications yet, just capture latest timestamp without firing toasts
            $query->orderBy('completed_at','desc');
        }

        $results = $query->limit(20)->get();
        $lastCompleted = $results->max('completed_at');

        return response()->json([
            'ok' => true,
            'count' => $results->count(),
            'data' => $results->map(function($r){
                return [
                    'id' => $r->id,
                    'patient' => $r->visitation?->pasien?->nama,
                    'test' => $r->labTest?->nama,
                    'completed_at' => optional($r->completed_at)->format('Y-m-d H:i:s'),
                ];
            }),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'last_completed_at' => $lastCompleted ? $lastCompleted->format('Y-m-d H:i:s') : null
        ]);
    }
}
