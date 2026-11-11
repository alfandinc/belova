<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\ERM\Visitation;
use App\Models\Finance\Invoice;
use App\Models\Finance\KepuasanPasien;
use App\Models\Finance\LayarPelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kasir side of the customer display: admin manages the tablets, the billing page pushes what the tablet shows.
 * The tablet's own (login-free) endpoints live in App\Http\Controllers\LayarPelangganDisplayController.
 */
class LayarPelangganController extends Controller
{
    // ------------------------------------------------------------------ admin: manage displays

    public function index()
    {
        $layars = LayarPelanggan::with('klinik:id,nama')->orderBy('nama')->get();
        $kliniks = Klinik::orderBy('nama')->get(['id', 'nama']);

        return view('finance.layar-pelanggan.index', compact('layars', 'kliniks'));
    }

    public function store(Request $request)
    {
        $data = $this->validateLayar($request);
        LayarPelanggan::create($data + ['token' => LayarPelanggan::newToken()]);

        return response()->json(['success' => true, 'message' => 'Layar pelanggan ditambahkan.']);
    }

    public function update(Request $request, LayarPelanggan $layar)
    {
        $layar->update($this->validateLayar($request));

        return response()->json(['success' => true, 'message' => 'Layar pelanggan diperbarui.']);
    }

    /** New link: the old one stops working (e.g. tablet lost or link leaked). */
    public function regenerateToken(LayarPelanggan $layar)
    {
        $layar->update(['token' => LayarPelanggan::newToken()]);

        return response()->json(['success' => true, 'message' => 'Link baru dibuat. Buka link baru di tablet.']);
    }

    public function destroy(LayarPelanggan $layar)
    {
        $layar->delete();

        return response()->json(['success' => true, 'message' => 'Layar pelanggan dihapus.']);
    }

    private function validateLayar(Request $request): array
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'klinik_id' => 'nullable|integer|exists:erm_klinik,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    // ------------------------------------------------------------------ kasir: billing page panel

    /** Active displays for the billing page panel (with online status and what each one shows). */
    public function kasirList()
    {
        $layars = LayarPelanggan::where('is_active', true)->orderBy('nama')->get()
            ->map(fn (LayarPelanggan $l) => [
                'id' => $l->id,
                'nama' => $l->nama,
                'online' => $l->isOnline(),
                'mode' => $l->currentState()['mode'] ?? 'idle',
            ]);

        return response()->json(['data' => $layars]);
    }

    public function push(Request $request, LayarPelanggan $layar)
    {
        abort_unless($layar->is_active, 404, 'Layar pelanggan nonaktif.');

        $mode = $request->input('mode');

        return match ($mode) {
            'billing' => $this->pushBilling($request, $layar),
            'survey' => $this->pushSurvey($request, $layar),
            'idle' => $this->pushIdle($request, $layar),
            default => response()->json(['success' => false, 'message' => 'Mode tidak dikenal.'], 422),
        };
    }

    private function pushBilling(Request $request, LayarPelanggan $layar)
    {
        $data = $request->validate([
            'visitation_id' => 'required|string|max:191',
            'force' => 'nullable|boolean',
            'items' => 'nullable|array|max:300',
            'items.*.nama' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|numeric',
            'items.*.harga' => 'nullable|numeric',
            'items.*.diskon' => 'nullable|string|max:50',
            'items.*.total' => 'nullable|numeric',
            'totals' => 'nullable|array',
            'totals.subtotal' => 'nullable|numeric',
            'totals.diskon' => 'nullable|numeric',
            'totals.pajak' => 'nullable|numeric',
            'totals.admin' => 'nullable|numeric',
            'totals.ongkir' => 'nullable|numeric',
            'totals.grand_total' => 'nullable|numeric',
        ]);

        // patient name / klinik come from the database, not from the page
        $visitation = Visitation::with(['pasien:id,nama', 'klinik:id,nama'])->find($data['visitation_id']);
        if (!$visitation) {
            return response()->json(['success' => false, 'message' => 'Kunjungan tidak ditemukan.'], 404);
        }

        return DB::transaction(function () use ($layar, $data, $visitation, $request) {
            $layar = LayarPelanggan::lockForUpdate()->find($layar->id);
            $current = $layar->currentState();

            // a patient still filling the survey is not interrupted by the next bill unless the kasir says so
            if (($current['mode'] ?? 'idle') === 'survey' && !$request->boolean('force')) {
                return response()->json([
                    'success' => false,
                    'blocked' => 'survey',
                    'message' => 'Layar sedang menampilkan survei untuk ' . ($current['pasien'] ?? 'pasien sebelumnya') . '.',
                ]);
            }

            $items = collect($data['items'] ?? [])->map(fn ($i) => [
                'nama' => (string) ($i['nama'] ?? '-'),
                'qty' => (float) ($i['qty'] ?? 1),
                'harga' => (float) ($i['harga'] ?? 0),
                'diskon' => (string) ($i['diskon'] ?? ''),
                'total' => (float) ($i['total'] ?? 0),
            ])->values()->all();

            $t = $data['totals'] ?? [];
            $layar->writeState([
                'mode' => 'billing',
                'visitation_id' => $visitation->id,
                'pasien' => optional($visitation->pasien)->nama ?? '-',
                'klinik' => optional($visitation->klinik)->nama,
                'items' => $items,
                'totals' => [
                    'subtotal' => (float) ($t['subtotal'] ?? 0),
                    'diskon' => (float) ($t['diskon'] ?? 0),
                    'pajak' => (float) ($t['pajak'] ?? 0),
                    'admin' => (float) ($t['admin'] ?? 0),
                    'ongkir' => (float) ($t['ongkir'] ?? 0),
                    'grand_total' => (float) ($t['grand_total'] ?? 0),
                ],
                'at' => time(),
            ]);

            return response()->json(['success' => true]);
        });
    }

    private function pushSurvey(Request $request, LayarPelanggan $layar)
    {
        $data = $request->validate(['invoice_id' => 'required|integer']);

        $invoice = Invoice::with(['visitation.pasien:id,nama', 'visitation.klinik:id,nama'])->find($data['invoice_id']);
        if (!$invoice || $invoice->status === 'canceled') {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan / dibatalkan.'], 404);
        }
        // survey only after the payment was processed (Buat Invoice alone leaves payment_method empty)
        if (empty($invoice->payment_method)) {
            return response()->json(['success' => false, 'message' => 'Pembayaran invoice ini belum diproses.'], 422);
        }
        if (KepuasanPasien::where('invoice_id', $invoice->id)->exists()) {
            return response()->json(['success' => false, 'already' => true, 'message' => 'Pasien sudah mengisi survei untuk invoice ini.']);
        }

        $layar->writeState([
            'mode' => 'survey',
            'invoice_id' => $invoice->id,
            'visitation_id' => $invoice->visitation_id,
            'pasien' => optional(optional($invoice->visitation)->pasien)->nama ?? '-',
            'klinik' => optional(optional($invoice->visitation)->klinik)->nama,
            'kasir_user_id' => auth()->id(),
            'at' => time(),
            'expires_at' => time() + LayarPelanggan::SURVEY_SECONDS,
        ]);

        return response()->json(['success' => true, 'message' => 'Survei ditampilkan di layar pasien.']);
    }

    private function pushIdle(Request $request, LayarPelanggan $layar)
    {
        // `only_visitation`: the billing page leaving only clears its own bill, never a survey or another patient
        $only = $request->input('only_visitation');

        return DB::transaction(function () use ($layar, $only) {
            $layar = LayarPelanggan::lockForUpdate()->find($layar->id);
            $current = $layar->currentState();
            if ($only !== null && (($current['mode'] ?? 'idle') !== 'billing' || (string) ($current['visitation_id'] ?? '') !== (string) $only)) {
                return response()->json(['success' => true, 'skipped' => true]);
            }
            if (($current['mode'] ?? 'idle') !== 'idle') {
                $layar->writeState(['mode' => 'idle', 'at' => time()]);
            }

            return response()->json(['success' => true]);
        });
    }
}
