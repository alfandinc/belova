<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Konsultasi;
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
        $konsultasis = Konsultasi::query()
            ->when($hasSpesialisasi, fn ($q) => $q->with('spesialisasis:id,nama'))
            ->orderBy('kategori')->orderBy('nama')->get();
        $spesialisasis = Spesialisasi::orderBy('nama')->get(['id', 'nama']);

        return view('marketing.konsultasi.index', compact('konsultasis', 'spesialisasis', 'hasSpesialisasi'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request), self::MESSAGES);

        $konsultasi = DB::transaction(function () use ($validated) {
            $konsultasi = Konsultasi::create(collect($validated)->only(['nama', 'kategori', 'harga'])->all());
            $this->syncSpesialisasi($konsultasi, $validated);

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

        DB::transaction(function () use ($konsultasi, $validated) {
            $konsultasi->update(collect($validated)->only(['nama', 'kategori', 'harga'])->all());
            $this->syncSpesialisasi($konsultasi, $validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Biaya konsultasi berhasil diperbarui.',
            'data' => $konsultasi,
        ]);
    }

    public function destroy(Konsultasi $konsultasi): JsonResponse
    {
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
    ];

    /**
     * A jenis konsultasi (name contains "konsultasi": the ones the dokter picks) needs at least one spesialisasi,
     * since unmapped means offered nowhere. Other fees on this page (member, ...) are never offered, so optional.
     */
    private function rules(Request $request, ?Konsultasi $konsultasi = null): array
    {
        $wajib = Konsultasi::hasSpesialisasiMapping() && str_contains(mb_strtolower((string) $request->input('nama')), 'konsultasi');

        return [
            'nama' => ['required', 'string', 'max:255', Rule::unique('erm_konsultasi', 'nama')->ignore($konsultasi?->id)],
            'kategori' => ['nullable', 'string', 'max:255'],
            'spesialisasi_ids' => $wajib ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'spesialisasi_ids.*' => ['integer', 'exists:erm_spesialisasis,id'],
            'harga' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** Before the pivot migration ran (prod) the spesialisasi choice is simply not saved. */
    private function syncSpesialisasi(Konsultasi $konsultasi, array $validated): void
    {
        if (Konsultasi::hasSpesialisasiMapping()) {
            $konsultasi->spesialisasis()->sync(array_unique(array_map('intval', $validated['spesialisasi_ids'] ?? [])));
        }
    }
}
