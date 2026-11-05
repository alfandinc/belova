<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiKategori;
use App\Models\ERM\RadiologiTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Master radiologi: pemeriksaan (test) with kategori and harga normal, and the kategori. A test that was ever
 * requested or is in a paket biaya konsultasi keeps its history, so it can not be deleted.
 */
class RadiologiMasterController extends Controller
{
    public function index()
    {
        return view('erm.eradiologi.master', [
            'kategoris' => RadiologiKategori::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function data()
    {
        $paket = Konsultasi::hasRadiologiPaket()
            ? DB::table('erm_konsultasi_radiologi as kr')->join('erm_konsultasi as k', 'k.id', '=', 'kr.konsultasi_id')
                ->get(['kr.radiologi_test_id', 'k.nama', 'kr.harga'])->groupBy('radiologi_test_id')
            : collect();

        $tests = RadiologiTest::with('radiologiKategori:id,nama')->withCount('radiologiPermintaan')->orderBy('nama')->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'kategori_id' => $t->radiologi_kategori_id,
                'kategori' => optional($t->radiologiKategori)->nama,
                'harga' => (float) $t->harga,
                'deskripsi' => $t->deskripsi,
                'dipakai' => (int) $t->radiologi_permintaan_count,
                'paket' => collect($paket->get($t->id, []))->map(fn ($p) => ['nama' => $p->nama, 'harga' => (float) $p->harga])->values(),
            ]);

        $kategoris = RadiologiKategori::withCount('radiologiTests')->orderBy('nama')->get()
            ->map(fn ($k) => ['id' => $k->id, 'nama' => $k->nama, 'jumlah' => (int) $k->radiologi_tests_count]);

        return response()->json(['tests' => $tests, 'kategoris' => $kategoris]);
    }

    // ------------------------------------------------------------------ test

    public function storeTest(Request $request)
    {
        $test = RadiologiTest::create($this->validateTest($request));

        return response()->json(['message' => 'Pemeriksaan ' . $test->nama . ' ditambahkan.']);
    }

    public function updateTest(Request $request, RadiologiTest $test)
    {
        $test->update($this->validateTest($request, $test));

        return response()->json(['message' => 'Pemeriksaan ' . $test->nama . ' disimpan.']);
    }

    public function destroyTest(RadiologiTest $test)
    {
        $usage = [
            'permintaan radiologi' => $test->radiologiPermintaan()->count(),
            'paket biaya konsultasi' => Konsultasi::hasRadiologiPaket()
                ? DB::table('erm_konsultasi_radiologi')->where('radiologi_test_id', $test->id)->count() : 0,
        ];
        $usedIn = collect($usage)->filter()->map(fn ($n, $label) => "{$n} {$label}")->values();
        if ($usedIn->isNotEmpty()) {
            return response()->json([
                'message' => $test->nama . ' sudah dipakai di ' . $usedIn->implode(', ') . ', jadi tidak bisa dihapus agar riwayatnya tetap utuh.',
            ], 422);
        }

        $test->delete();

        return response()->json(['message' => 'Pemeriksaan ' . $test->nama . ' dihapus.']);
    }

    private function validateTest(Request $request, ?RadiologiTest $test = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255',
                Rule::unique('erm_radiologi_test', 'nama')
                    ->where('radiologi_kategori_id', $request->input('radiologi_kategori_id'))
                    ->ignore($test?->id)],
            'radiologi_kategori_id' => ['required', 'exists:erm_radiologi_kategori,id'],
            'harga' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ], [
            'nama.required' => 'Nama pemeriksaan wajib diisi.',
            'nama.unique' => 'Nama pemeriksaan sudah ada di kategori ini.',
            'radiologi_kategori_id.required' => 'Pilih kategori.',
            'harga.required' => 'Isi harga normal.',
        ]);
    }

    // ------------------------------------------------------------------ kategori

    public function storeKategori(Request $request)
    {
        $kategori = RadiologiKategori::create($this->validateKategori($request));

        return response()->json(['message' => 'Kategori ' . $kategori->nama . ' ditambahkan.']);
    }

    public function updateKategori(Request $request, RadiologiKategori $kategori)
    {
        $kategori->update($this->validateKategori($request, $kategori));

        return response()->json(['message' => 'Kategori ' . $kategori->nama . ' disimpan.']);
    }

    public function destroyKategori(RadiologiKategori $kategori)
    {
        if ($kategori->radiologiTests()->exists()) {
            return response()->json(['message' => 'Kategori ' . $kategori->nama . ' masih berisi pemeriksaan. Pindahkan atau hapus pemeriksaannya dulu.'], 422);
        }
        $kategori->delete();

        return response()->json(['message' => 'Kategori ' . $kategori->nama . ' dihapus.']);
    }

    private function validateKategori(Request $request, ?RadiologiKategori $kategori = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('erm_radiologi_kategori', 'nama')->ignore($kategori?->id)],
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique' => 'Kategori ini sudah ada.',
        ]);
    }
}
