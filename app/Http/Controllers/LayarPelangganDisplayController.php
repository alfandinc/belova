<?php

namespace App\Http\Controllers;

use App\Models\ERM\Visitation;
use App\Models\Finance\Invoice;
use App\Models\Finance\KepuasanPasien;
use App\Models\Finance\LayarPelanggan;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The tablet facing the patient. No login: the secret token in the URL identifies the display
 * (routes are excluded from CSRF, so a tablet left on for days never hits a 419).
 */
class LayarPelangganDisplayController extends Controller
{
    public function show(string $token)
    {
        $layar = $this->findLayar($token);

        return view('layar-pelanggan.display', [
            'layar' => $layar,
            'stateUrl' => url('/layar-pelanggan/' . $token . '/state'),
            'surveyUrl' => url('/layar-pelanggan/' . $token . '/survey'),
        ]);
    }

    /**
     * Polled every ~1.5s. `k` = the key the tablet already has; unchanged → tiny response.
     * The key includes the effective mode so an expired survey / stale bill also counts as a change.
     */
    public function state(Request $request, string $token)
    {
        $layar = $this->findLayar($token);

        if ($layar->last_seen_at === null || $layar->last_seen_at->lt(now()->subSeconds(15))) {
            // heartbeat for the "online" dot on the kasir page; no updated_at bump
            DB::table('finance_layar_pelanggan')->where('id', $layar->id)->update(['last_seen_at' => now()]);
        }

        $state = $layar->currentState();
        $key = $layar->state_version . ':' . ($state['mode'] ?? 'idle');

        if ((string) $request->query('k') === $key) {
            return response()->json(['k' => $key, 'same' => true]);
        }

        // only what the screen needs (no internal ids beyond the survey's invoice)
        $public = ['mode' => $state['mode'] ?? 'idle'];
        if ($public['mode'] === 'billing') {
            $public += [
                'pasien' => $state['pasien'] ?? '-',
                'klinik' => $state['klinik'] ?? null,
                'items' => $state['items'] ?? [],
                'totals' => $state['totals'] ?? [],
            ];
        } elseif ($public['mode'] === 'hold') {
            $public['klinik'] = $state['klinik'] ?? null;
        } elseif ($public['mode'] === 'survey') {
            $public += [
                'invoice_id' => $state['invoice_id'] ?? null,
                'pasien' => $state['pasien'] ?? '-',
                'klinik' => $state['klinik'] ?? null,
                'remaining' => max(0, (int) ($state['expires_at'] ?? 0) - time()),
            ];
        }

        return response()->json(['k' => $key, 'state' => $public]);
    }

    public function survey(Request $request, string $token)
    {
        $layar = $this->findLayar($token);

        $data = $request->validate([
            'invoice_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'keluhan' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($layar, $data) {
            $layar = LayarPelanggan::lockForUpdate()->find($layar->id);
            $state = $layar->currentState();

            // only the survey currently on screen can be answered
            if (($state['mode'] ?? 'idle') !== 'survey' || (int) ($state['invoice_id'] ?? 0) !== (int) $data['invoice_id']) {
                return response()->json(['success' => false, 'message' => 'Survei sudah tidak berlaku.'], 409);
            }

            $invoice = Invoice::find($data['invoice_id']);
            $visitation = $invoice ? Visitation::find($invoice->visitation_id) : null;
            $keluhan = trim((string) ($data['keluhan'] ?? ''));

            try {
                KepuasanPasien::create([
                    'invoice_id' => (int) $data['invoice_id'],
                    'visitation_id' => $invoice->visitation_id ?? null,
                    'pasien_id' => $visitation->pasien_id ?? null,
                    'klinik_id' => $visitation->klinik_id ?? null,
                    'dokter_id' => $visitation->dokter_id ?? null,
                    'layar_pelanggan_id' => $layar->id,
                    'kasir_user_id' => $state['kasir_user_id'] ?? null,
                    'rating' => (int) $data['rating'],
                    'keluhan' => $keluhan !== '' ? $keluhan : null,
                ]);
            } catch (QueryException $e) {
                // double tap: unique invoice_id → already saved, treat as success
                if (!KepuasanPasien::where('invoice_id', $data['invoice_id'])->exists()) {
                    throw $e;
                }
            }

            $layar->writeState(['mode' => 'idle', 'at' => time()]);

            return response()->json(['success' => true]);
        });
    }

    private function findLayar(string $token): LayarPelanggan
    {
        $layar = LayarPelanggan::where('token', $token)->first();
        abort_unless($layar && $layar->is_active, 404);

        return $layar;
    }
}
