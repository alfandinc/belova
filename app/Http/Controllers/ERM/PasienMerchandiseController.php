<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Merchandise;
use App\Models\ERM\MerchandiseKartuStok;
use App\Models\ERM\PasienMerchandise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Pasien;

class PasienMerchandiseController extends Controller
{
    protected function getCurrentMonthNetUsedQty(int $merchandiseId): int
    {
        return (int) PasienMerchandise::where('merchandise_id', $merchandiseId)
            ->whereBetween('given_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity');
    }

    protected function getRemainingMonthlyLimit(Merchandise $merchandise): ?int
    {
        if ($merchandise->monthly_limit_stock === null) {
            return null;
        }

        return max(0, (int) $merchandise->monthly_limit_stock - $this->getCurrentMonthNetUsedQty($merchandise->id));
    }

    protected function ensureMonthlyLimitAvailable(Merchandise $merchandise, int $requestedQty): void
    {
        $remaining = $this->getRemainingMonthlyLimit($merchandise);

        if ($remaining !== null && $requestedQty > $remaining) {
            abort(response()->json([
                'success' => false,
                'message' => 'Qty merchandise melebihi limit stock bulanan. Sisa limit bulan ini: ' . $remaining,
                'remaining_monthly_stock' => $remaining,
                'monthly_limit_stock' => (int) $merchandise->monthly_limit_stock,
            ], 422));
        }
    }

    protected function recordKartuStok(Merchandise $merchandise, string $type, int $qty, string $notes): void
    {
        MerchandiseKartuStok::create([
            'merchandise_id' => $merchandise->id,
            'tanggal' => now(),
            'type' => $type,
            'qty' => $qty,
            'current_stock' => MerchandiseKartuStok::calculateCurrentStock($merchandise->id, $type, $qty),
            'notes' => $notes,
        ]);
    }

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

        $quantity = (int) ($data['quantity'] ?? 1);
        $merchandise = Merchandise::findOrFail($data['merchandise_id']);

        $rec = DB::transaction(function () use ($pasien, $data, $quantity, $merchandise) {
            $this->ensureMonthlyLimitAvailable($merchandise, $quantity);

            $rec = PasienMerchandise::create([
                'pasien_id' => $pasien->id,
                'merchandise_id' => $data['merchandise_id'],
                'quantity' => $quantity,
                'notes' => $data['notes'] ?? null,
                'given_by_user_id' => Auth::check() ? Auth::id() : null,
                'given_at' => now()
            ]);

            $this->recordKartuStok(
                $merchandise,
                'out',
                $quantity,
                'Pemberian merchandise ke pasien ' . $pasien->id . ' - ' . ($pasien->nama ?? '-')
            );

            return $rec;
        });

        return response()->json([
            'success' => true,
            'id' => $rec->id,
            'data' => $rec,
            'remaining_monthly_stock' => $this->getRemainingMonthlyLimit($merchandise),
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

        $rec = PasienMerchandise::where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $data = $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $merchandise = Merchandise::findOrFail($rec->merchandise_id);
        $newQty = (int) ($data['quantity'] ?? $rec->quantity);
        $oldQty = (int) $rec->quantity;
        $delta = $newQty - $oldQty;

        DB::transaction(function () use ($pasien, $data, $rec, $merchandise, $newQty, $delta) {
            if ($delta > 0) {
                $this->ensureMonthlyLimitAvailable($merchandise, $delta);
            }

            if ($delta !== 0) {
                $this->recordKartuStok(
                    $merchandise,
                    $delta > 0 ? 'out' : 'in',
                    abs($delta),
                    'Penyesuaian merchandise pasien ' . $pasien->id . ' - ' . ($pasien->nama ?? '-')
                );
            }

            $rec->quantity = $newQty;
            if (array_key_exists('notes', $data)) {
                $rec->notes = $data['notes'];
            }
            $rec->save();
        });

        return response()->json([
            'success' => true,
            'data' => $rec->fresh(),
            'remaining_monthly_stock' => $this->getRemainingMonthlyLimit($merchandise),
        ]);
    }

    /**
     * Remove a merchandise receipt from a pasien
     * DELETE /erm/pasiens/{id}/merchandises/{pmId}
     */
    public function destroy(Request $request, $id, $pmId)
    {
        $pasien = Pasien::find($id);
        if (!$pasien) return response()->json(['error' => 'Pasien not found'], 404);

        $rec = PasienMerchandise::where('id', $pmId)->where('pasien_id', $pasien->id)->first();
        if (!$rec) return response()->json(['error' => 'Record not found'], 404);

        $merchandise = Merchandise::findOrFail($rec->merchandise_id);

        DB::transaction(function () use ($pasien, $rec, $merchandise) {
            $this->recordKartuStok(
                $merchandise,
                'in',
                (int) $rec->quantity,
                'Pembatalan merchandise pasien ' . $pasien->id . ' - ' . ($pasien->nama ?? '-')
            );

            $rec->delete();
        });

        return response()->json([
            'success' => true,
            'remaining_monthly_stock' => $this->getRemainingMonthlyLimit($merchandise),
        ]);
    }
}
