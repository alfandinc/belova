<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\BillingController;
use App\Models\ERM\Visitation;
use App\Services\ERM\AlurPelayanan;
use App\Services\Finance\BillingRowFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Tahap Pelayanan strip / badges / timeline shared by Rawat Jalan, Billing and E-Resep.
 */
class AlurPelayananController extends Controller
{
    /** Strip counts for the page filters + the stage of the table rows shown (ids[]). */
    public function summary(Request $request)
    {
        $filters = $this->filters($request);

        return response()->json([
            'counts' => AlurPelayanan::counts($filters),
            'late' => AlurPelayanan::lateCounts($filters),
            'rows' => AlurPelayanan::forVisits((array) $request->input('ids', [])),
        ]);
    }

    /** Visits in one stage, longest waiting first (strip click). */
    public function list(Request $request)
    {
        $stage = (string) $request->input('tahap');
        abort_unless(array_key_exists($stage, AlurPelayanan::STAGES), 422, 'Tahap tidak dikenal');

        return response()->json(['data' => AlurPelayanan::listStage($this->filters($request), $stage)]);
    }

    public function timeline(string $visitationId)
    {
        $timeline = AlurPelayanan::timeline($visitationId);
        abort_unless($timeline, 404);

        return response()->json($timeline);
    }

    /**
     * Read-only bill of a visit for the timeline modal (perawat / pendaftaran can tell the patient the items and prices).
     * Items come from the kasir's own billing list in light mode (never writes), so names / prices match the kasir;
     * a paid invoice shows its frozen item snapshot.
     */
    public function tagihan(string $visitationId)
    {
        $visitation = Visitation::find($visitationId);
        abort_unless($visitation, 404);

        $itemsRequest = Request::create('/', 'GET', ['light' => 1]);
        $itemsRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
        try {
            $payload = app(BillingController::class)->create($itemsRequest, $visitation->id)->getData(true);
        } catch (\Throwable $e) {
            report($e);
            abort(500, 'Gagal memuat tagihan');
        }

        $items = collect($payload['data'] ?? [])->map(function ($row) {
            $isRacikan = !empty($row['is_racikan']);
            $qty = (float) ($isRacikan ? ($row['racikan_bungkus'] ?? 1) : ($row['qty'] ?? $row['quantity'] ?? 1));
            $total = (float) ($row['harga_akhir_raw'] ?? $row['final_amount'] ?? 0);
            $harga = (float) ($row['jumlah_raw'] ?? $row['unit_price'] ?? 0);

            return [
                'nama' => strip_tags((string) ($row['nama_item'] ?? $row['name'] ?? '-')),
                'qty' => $qty ?: 1,
                'harga' => $harga,
                'diskon' => max(0, round($harga * ($qty ?: 1) - $total, 2)),
                'total' => $total,
            ];
        })->values();

        $invoice = $visitation->invoice()->with('piutangs')->first();
        $billingCounts = DB::table('finance_billing')->where('visitation_id', $visitation->id)
            ->selectRaw('COUNT(*) as total, SUM(deleted_at IS NOT NULL) as trashed')->first();

        return response()->json([
            'items' => $items,
            'total_item' => round($items->sum('total'), 2),
            'invoice' => $invoice ? [
                'nomor' => $invoice->invoice_number,
                'total' => (float) $invoice->total_amount,
                'dibayar' => (float) $invoice->amount_paid,
                'kembalian' => (float) ($invoice->change_amount ?? 0),
            ] : null,
            'status' => BillingRowFormatter::statusLabel($invoice, (int) ($billingCounts->total ?? 0), (int) ($billingCounts->trashed ?? 0)),
            // Unpaid bills can still change at the kasir (diskon, item added / removed)
            'final' => AlurPelayanan::isTransacted($visitation->id),
        ]);
    }

    private function filters(Request $request): array
    {
        $filters = $request->only(['start_date', 'end_date', 'klinik_id', 'dokter_id', 'metode_group']);
        if (!in_array($filters['metode_group'] ?? null, ['umum', 'asuransi'], true)) {
            $filters['metode_group'] = null;
        }
        foreach (['start_date', 'end_date'] as $key) {
            if (!empty($filters[$key]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters[$key])) {
                $filters[$key] = null;
            }
        }
        $filters['dokter_id'] = ($filters['dokter_id'] ?? null)
            ?: AlurPelayanan::defaultDokterId(Auth::user(), $request->has('dokter_id'));

        return $filters;
    }
}
