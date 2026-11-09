<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Merchandise;
use App\Models\ERM\Pasien;
use App\Models\ERM\PasienMerchandise;
use App\Services\ERM\MerchandiseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasienMerchandiseController extends Controller
{
    public function __construct(private MerchandiseStockService $stock)
    {
    }

    /**
     * Items for the give/stock-out forms, for every role that has a Merchandise button.
     *
     * GET /erm/merchandises/catalog
     */
    public function catalog(Request $request)
    {
        return response()->json([
            'data' => $this->stock->catalog($request->query('pasien_id'), $request->boolean('include_inactive')),
            'can_give' => (bool) Auth::user()?->hasAnyRole(MerchandiseStockService::GIVE_ROLES),
        ]);
    }

    /**
     * GET /erm/merchandises/{id}/stock-history
     */
    public function stockHistory($id)
    {
        $merchandise = Merchandise::find($id);
        if (!$merchandise) return response()->json(['error' => 'Not found'], 404);

        return response()->json(['success' => true, 'data' => $this->stock->history($merchandise)]);
    }

    /**
     * Return a list of merchandises a pasien currently holds.
     *
     * GET /erm/pasiens/{id}/merchandises
     */
    public function index(Request $request, $id)
    {
        $pasien = Pasien::find($id);

        if (!$pasien) {
            return response()->json(['error' => 'Pasien not found'], 404);
        }

        $items = $pasien->pasienMerchandises()->active()->with(['merchandise', 'givenBy:id,name'])->orderByDesc('given_at')->orderByDesc('id')->get()->map(function ($rec) {
            return [
                'id' => $rec->id,
                'merchandise_id' => $rec->merchandise->id ?? null,
                'merchandise_name' => $rec->merchandise->name ?? null,
                'quantity' => $rec->quantity,
                'notes' => $rec->notes,
                'given_by_user_id' => $rec->given_by_user_id,
                'given_by_name' => $rec->givenBy->name ?? null,
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

        $rec = $this->stock->giveToPasien(
            $pasien,
            (int) $data['merchandise_id'],
            (int) ($data['quantity'] ?? 1),
            $data['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'id' => $rec->id,
            'data' => $rec,
        ]);
    }

    /**
     * Update a pasien merchandise record (quantity, notes)
     * PUT /erm/pasiens/{id}/merchandises/{pmId}
     */
    public function update(Request $request, $id, $pmId)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $rec = PasienMerchandise::active()->where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $data = $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $rec = $this->stock->updatePasienRecord(
            $pasien,
            $rec,
            (int) ($data['quantity'] ?? $rec->quantity),
            array_intersect_key($data, ['notes' => true])
        );

        return response()->json([
            'success' => true,
            'data' => $rec->fresh(),
        ]);
    }

    /**
     * Cancel a merchandise receipt. The record stays for audit.
     * DELETE /erm/pasiens/{id}/merchandises/{pmId}
     */
    public function destroy(Request $request, $id, $pmId)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $rec = PasienMerchandise::active()->where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'return_stock' => 'required|boolean',
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'return_stock.required' => 'Pilih apakah barangnya kembali.',
        ]);

        $returned = $this->stock->cancelPasienRecord($pasien, $rec, $data['reason'], (bool) $data['return_stock']);

        $message = 'Pemberian dibatalkan. Stok tidak berubah.';
        if ($returned > 0) {
            $message = 'Pemberian dibatalkan. Stok kembali ' . $returned . '.';
        } elseif ($data['return_stock']) {
            $message = 'Pemberian dibatalkan. Stok tidak berubah karena pemberian ini tidak pernah mengurangi stok.';
        }

        return response()->json(['success' => true, 'stock_returned' => $returned, 'message' => $message]);
    }
}
