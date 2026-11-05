<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiPermintaan;
use App\Models\Finance\Billing;
use App\Models\ERM\RadiologiTest;
use App\Models\ERM\Spesialisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KonsultasiMasterController extends Controller
{
    public function index()
    {
        $hasSpesialisasi = Konsultasi::hasSpesialisasiMapping();
        $hasRadiologi = Konsultasi::hasRadiologiPaket();
        $konsultasis = Konsultasi::query()
            ->when($hasSpesialisasi, fn ($q) => $q->with('spesialisasis:id,nama'))
            ->when($hasRadiologi, fn ($q) => $q->with('radiologiTests:id,nama'))
            ->orderBy('kategori')->orderBy('nama')->get();
        $spesialisasis = Spesialisasi::orderBy('nama')->get(['id', 'nama']);
        $radiologiTests = $hasRadiologi ? RadiologiTest::orderBy('nama')->get(['id', 'nama', 'harga']) : collect();
        // Used in a billing / invoice: can not be deleted. Paket radiologi already billed: its radiologi is locked.
        $dipakaiIds = self::dipakaiQuery()->flip();
        $terkunciIds = $hasRadiologi ? self::radiologiTerkunciIds($dipakaiIds->keys()->all())->flip() : collect();

        return view('marketing.konsultasi.index', compact('konsultasis', 'spesialisasis', 'hasSpesialisasi', 'hasRadiologi', 'radiologiTests', 'dipakaiIds', 'terkunciIds'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request), self::MESSAGES);

        $konsultasi = DB::transaction(function () use ($validated) {
            $konsultasi = Konsultasi::create(collect($validated)->only(['nama', 'kategori', 'harga'])->all());
            $this->syncSpesialisasi($konsultasi, $validated);
            $this->syncRadiologi($konsultasi, $validated);

            return $konsultasi;
        });

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil ditambahkan.',
            'data' => $konsultasi,
        ]);
    }

    public function update(Request $request, Konsultasi $konsultasi): JsonResponse
    {
        $validated = $request->validate($this->rules($request, $konsultasi), self::MESSAGES);
        $this->assertRadiologiTidakBerubah($konsultasi, $validated);

        DB::transaction(function () use ($konsultasi, $validated) {
            $konsultasi->update(collect($validated)->only(['nama', 'kategori', 'harga'])->all());
            $this->syncSpesialisasi($konsultasi, $validated);
            $this->syncRadiologi($konsultasi, $validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil diperbarui.',
            'data' => $konsultasi,
        ]);
    }

    public function destroy(Konsultasi $konsultasi): JsonResponse
    {
        if (self::dipakaiQuery($konsultasi->id)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '"' . $konsultasi->nama . '" sudah dipakai di billing / invoice, jadi tidak bisa dihapus (riwayatnya akan hilang). '
                    . 'Centang "Nonaktif" di Edit agar tidak muncul lagi di dokter.',
            ], 422);
        }

        DB::transaction(function () use ($konsultasi) {
            if (Konsultasi::hasSpesialisasiMapping()) {
                $konsultasi->spesialisasis()->detach();
            }
            $konsultasi->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil dihapus.',
        ]);
    }

    private const MESSAGES = [
        'spesialisasi_ids.required' => 'Pilih minimal satu spesialisasi (jenis konsultasi tanpa spesialisasi tidak muncul di dokter).',
        'spesialisasi_ids.min' => 'Pilih minimal satu spesialisasi (jenis konsultasi tanpa spesialisasi tidak muncul di dokter).',
        'radiologi.*.test_id.distinct' => 'Pemeriksaan radiologi yang sama dipilih dua kali.',
        'radiologi.*.test_id.required' => 'Pilih pemeriksaan radiologi.',
        'radiologi.*.harga.required' => 'Isi harga radiologi dalam paket.',
    ];

    /**
     * A jenis konsultasi (name contains "konsultasi": the ones the dokter picks) needs at least one spesialisasi,
     * since unmapped means offered nowhere. Other fees on this page (member, ...) are never offered, so optional.
     */
    private function rules(Request $request, ?Konsultasi $konsultasi = null): array
    {
        $wajib = Konsultasi::hasSpesialisasiMapping() && !$request->boolean('nonaktif')
            && str_contains(mb_strtolower((string) $request->input('nama')), 'konsultasi');

        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('erm_konsultasi', 'nama')->ignore($konsultasi?->id)],
            'kategori' => ['nullable', 'string', 'max:255'],
            'nonaktif' => ['nullable', 'boolean'],
            'spesialisasi_ids' => $wajib ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'spesialisasi_ids.*' => ['integer', 'exists:erm_spesialisasis,id'],
            'harga' => ['required', 'numeric', 'min:0'],
            // Radiologi included in the harga (paket): harga = its part, the konsultasi billing is the rest
            'radiologi' => ['nullable', 'array'],
            'radiologi.*.test_id' => ['required', 'integer', 'distinct', 'exists:erm_radiologi_test,id'],
            'radiologi.*.harga' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** Konsultasi ids found in a billing (also deleted ones) or an invoice item; one id when given */
    private static function dipakaiQuery(?int $id = null)
    {
        $billing = Billing::withTrashed()->where('billable_type', Konsultasi::class)
            ->when($id, fn ($q) => $q->where('billable_id', $id))->distinct()->pluck('billable_id');
        $invoice = DB::table('finance_invoice_items')->where('billable_type', Konsultasi::class)
            ->when($id, fn ($q) => $q->where('billable_id', $id))->distinct()->pluck('billable_id');

        return $billing->concat($invoice)->map(fn ($v) => (int) $v)->unique()->values();
    }

    /**
     * Paket whose radiologi is fixed: used in a billing / invoice (the rekap counts those transactions with it) and
     * set before today (the day it is set it can still be corrected), or that already created paket permintaan.
     */
    private static function radiologiTerkunciIds(array $dipakaiIds)
    {
        $lama = $dipakaiIds ? DB::table('erm_konsultasi_radiologi')->whereIn('konsultasi_id', $dipakaiIds)
            ->where('created_at', '<', now()->startOfDay())->distinct()->pluck('konsultasi_id') : collect();
        $paket = RadiologiPermintaan::where('sumber_konsultasi', RadiologiPermintaan::SUMBER_PAKET)->distinct()->pluck('konsultasi_id');

        return $lama->concat($paket)->map(fn ($id) => (int) $id)->unique()->values();
    }

    /**
     * Once a paket is locked (see radiologiTerkunciIds), its radiologi (test + harga) is fixed: the rekap counts the
     * invoices from before the paket with it, so changing it would rewrite history. A new rule = a new item.
     */
    private function assertRadiologiTidakBerubah(Konsultasi $konsultasi, array $validated): void
    {
        if (!Konsultasi::hasRadiologiPaket()
            || self::radiologiTerkunciIds(self::dipakaiQuery($konsultasi->id)->all())->doesntContain($konsultasi->id)) {
            return;
        }
        $lama = $konsultasi->radiologiTests()->get()->mapWithKeys(fn ($t) => [$t->id => round((float) $t->pivot->harga, 2)])->sortKeys()->all();
        $baru = collect($validated['radiologi'] ?? [])->mapWithKeys(fn ($r) => [(int) $r['test_id'] => round((float) $r['harga'], 2)])->sortKeys()->all();

        if ($lama != $baru) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'radiologi' => 'Radiologi paket ini sudah dipakai transaksi, jadi tidak bisa diubah. Untuk aturan baru nonaktifkan item ini dan pakai Tambahan konsultasi di Master Radiologi.',
            ]);
        }
    }

    private function syncRadiologi(Konsultasi $konsultasi, array $validated): void
    {
        if (!Konsultasi::hasRadiologiPaket()) {
            return;
        }
        $rows = collect($validated['radiologi'] ?? []);
        // Paket only records the USG of old items already billed that way; new rules use Tambahan (Master Radiologi)
        if ($rows->isNotEmpty() && !$konsultasi->radiologiTests()->exists()
            && ($konsultasi->wasRecentlyCreated || self::dipakaiQuery($konsultasi->id)->isEmpty())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'radiologi' => 'Termasuk radiologi hanya untuk item lama yang sudah punya transaksi. Untuk konsultasi baru centang Tambahan konsultasi di Master Radiologi.',
            ]);
        }
        if ($rows->sum('harga') > (float) $validated['harga']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'radiologi' => 'Total harga radiologi dalam paket melebihi harga konsultasi.',
            ]);
        }
        $konsultasi->radiologiTests()->sync($rows->mapWithKeys(fn ($r) => [(int) $r['test_id'] => ['harga' => $r['harga']]])->all());
    }

    /** Before the pivot migration ran (prod) the spesialisasi choice is simply not saved. */
    private function syncSpesialisasi(Konsultasi $konsultasi, array $validated): void
    {
        if (Konsultasi::hasSpesialisasiMapping()) {
            // Nonaktif = no spesialisasi: offered to no dokter, the item and its history stay
            $ids = !empty($validated['nonaktif']) ? [] : ($validated['spesialisasi_ids'] ?? []);
            $konsultasi->spesialisasis()->sync(array_unique(array_map('intval', $ids)));
        }
    }
}
