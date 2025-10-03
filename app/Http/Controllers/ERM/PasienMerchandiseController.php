<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\Pasien;

class PasienMerchandiseController extends Controller
{
    /**
     * Return a list of merchandises a pasien has received.
     *
     * GET /erm/pasiens/{id}/merchandises
     */
    public function index(Request $request, $id)
    {
        $pasien = Pasien::with(['pasienMerchandises.merchandise'])->find($id);

        if (!$pasien) {
            return response()->json(['error' => 'Pasien not found'], 404);
        }

        $items = $pasien->pasienMerchandises->map(function($rec) {
            return [
                'id' => $rec->id,
                'merchandise_id' => $rec->merchandise->id ?? null,
                'merchandise_name' => $rec->merchandise->name ?? null,
                'quantity' => $rec->quantity,
                'notes' => $rec->notes,
                'given_by_user_id' => $rec->given_by_user_id,
                'given_at' => $rec->given_at,
                'created_at' => $rec->created_at,
            ];
        });

        return response()->json(['data' => $items]);
    }

    /**
     * Store a merchandise receipt for a pasien
     * POST /erm/pasiens/{id}/merchandises
     */
    public function store(Request $request, $id)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $data = $request->validate([
            'merchandise_id' => 'required|integer|exists:erm_merchandises,id',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $rec = \App\Models\ERM\PasienMerchandise::create([
            'pasien_id' => $pasien->id,
            'merchandise_id' => $data['merchandise_id'],
            'quantity' => $data['quantity'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'given_by_user_id' => Auth::check() ? Auth::id() : null,
            'given_at' => now()
        ]);

        return response()->json(['success' => true, 'id' => $rec->id, 'data' => $rec]);
    }

    /**
     * Update a pasien merchandise record (quantity, notes)
     * PUT /erm/pasiens/{id}/merchandises/{pmId}
     */
    public function update(Request $request, $id, $pmId)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $rec = \App\Models\ERM\PasienMerchandise::where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $data = $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $rec->quantity = $data['quantity'] ?? $rec->quantity;
        if (array_key_exists('notes', $data)) $rec->notes = $data['notes'];
        $rec->save();

        return response()->json(['success' => true, 'data' => $rec]);
    }

    /**
     * Remove a merchandise receipt from a pasien
     * DELETE /erm/pasiens/{id}/merchandises/{pmId}
     */
    public function destroy(Request $request, $id, $pmId)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $rec = \App\Models\ERM\PasienMerchandise::where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $rec->delete();
        return response()->json(['success' => true]);
    }
}
