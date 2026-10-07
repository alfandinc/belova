<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\PaketRacikan;
use App\Models\ERM\PaketRacikanDetail;
use App\Models\ERM\ResepFarmasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master Data > Paket Racikan: templates a dokter/farmasi applies to a resep as one racikan.
 * Only active pakets show up in the e-resep picker and are used to name racikan on billing/etiket.
 * Dosis is stored as a plain number in the obat's own satuan (Obat::satuan), like racikan dosis in e-resep.
 */
class PaketRacikanController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return view('erm.paket-racikan.index');
        }

        $query = PaketRacikan::query()->with([
            'wadah:id,nama',
            // include inactive obat so they can be flagged instead of looking deleted
            'details.obat' => fn ($q) => $q->withInactive()->select('id', 'nama', 'dosis', 'satuan', 'harga_nonfornas', 'status_aktif'),
        ]);

        $status = $request->input('status');
        if ($status === 'aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'nonaktif') {
            $query->where('is_active', false);
        }

        return DataTables::of($query)
            ->filter(function ($q) use ($request) {
                $term = trim((string) $request->input('search.value', ''));
                if ($term === '') {
                    return;
                }
                $q->where(function ($sub) use ($term) {
                    $sub->where('nama_paket', 'like', "%{$term}%")
                        ->orWhereHas('details.obat', fn ($o) => $o->withInactive()->where('nama', 'like', "%{$term}%"));
                });
            })
            ->addColumn('obats', function ($paket) {
                return $paket->details->map(function ($d) {
                    $obat = $d->obat;
                    return [
                        'nama' => $obat->nama ?? '(obat dihapus)',
                        'dosis' => trim(PaketRacikanDetail::normalizeDosis($d->dosis) . ' ' . ($obat->satuan ?? '')),
                        'aktif' => $obat && (int) $obat->status_aktif === 1,
                    ];
                })->values()->all();
            })
            ->addColumn('wadah_nama', fn ($paket) => $paket->wadah->nama ?? null)
            ->addColumn('harga_per_bungkus', fn ($paket) => round($this->hargaPerBungkus($paket)))
            ->removeColumn('details', 'wadah')
            // the page escapes every value itself; server escaping on top would show "&amp;"
            ->escapeColumns([])
            ->make(true);
    }

    public function show($id)
    {
        $paket = PaketRacikan::with([
            'wadah:id,nama,harga',
            'details.obat' => fn ($q) => $q->withInactive()->select('id', 'nama', 'dosis', 'satuan', 'status_aktif'),
        ])->findOrFail($id);

        return response()->json([
            'id' => $paket->id,
            'nama_paket' => $paket->nama_paket,
            'deskripsi' => $paket->deskripsi,
            'wadah' => $paket->wadah ? ['id' => $paket->wadah->id, 'text' => $paket->wadah->nama . ' ' . $paket->wadah->harga] : null,
            'bungkus_default' => $paket->bungkus_default,
            'aturan_pakai_default' => $paket->aturan_pakai_default,
            'is_active' => $paket->is_active,
            'details' => $paket->details->map(function ($d) {
                $obat = $d->obat;
                return [
                    'obat_id' => $d->obat_id,
                    'obat_text' => $obat
                        ? trim($obat->nama . ' - ' . $obat->dosis . ' ' . $obat->satuan) . ((int) $obat->status_aktif === 1 ? '' : ' (nonaktif)')
                        : '(obat dihapus)',
                    'satuan' => $obat->satuan ?? '',
                    'dosis' => PaketRacikanDetail::normalizeDosis($d->dosis),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePaket($request);

        $paket = DB::transaction(function () use ($validated) {
            $paket = PaketRacikan::create([
                'nama_paket' => $validated['nama_paket'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'wadah_id' => $validated['wadah_id'] ?? null,
                'bungkus_default' => $validated['bungkus_default'],
                'aturan_pakai_default' => $validated['aturan_pakai_default'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => Auth::id(),
            ]);
            $this->syncDetails($paket, $validated['obats']);
            return $paket;
        });

        return response()->json(['success' => true, 'message' => 'Paket racikan berhasil ditambahkan.', 'id' => $paket->id]);
    }

    public function update(Request $request, $id)
    {
        $paket = PaketRacikan::findOrFail($id);
        $validated = $this->validatePaket($request, $paket->id);

        DB::transaction(function () use ($paket, $validated) {
            $paket->update([
                'nama_paket' => $validated['nama_paket'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'wadah_id' => $validated['wadah_id'] ?? null,
                'bungkus_default' => $validated['bungkus_default'],
                'aturan_pakai_default' => $validated['aturan_pakai_default'] ?? null,
                'is_active' => $validated['is_active'] ?? $paket->is_active,
            ]);
            $this->syncDetails($paket, $validated['obats']);
        });

        return response()->json(['success' => true, 'message' => 'Paket racikan berhasil diperbarui.']);
    }

    public function toggle($id)
    {
        $paket = PaketRacikan::findOrFail($id);
        $paket->update(['is_active' => !$paket->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $paket->is_active,
            'message' => $paket->is_active ? 'Paket racikan diaktifkan.' : 'Paket racikan dinonaktifkan.',
        ]);
    }

    public function destroy($id)
    {
        // Details are removed by the FK cascade; resep rows keep their own obat/dosis copy.
        PaketRacikan::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Paket racikan berhasil dihapus.']);
    }

    private function validatePaket(Request $request, $exceptId = null): array
    {
        // Every rule has its own message: the app locale is "id" without lang files, so defaults show raw keys.
        $validated = $request->validate([
            'nama_paket' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'wadah_id' => 'nullable|exists:erm_wadah_obat,id',
            'bungkus_default' => 'required|integer|min:1',
            'aturan_pakai_default' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'obats' => 'required|array|min:1',
            // inactive obat would be copied into resep without a price, so a paket may only hold active obat
            'obats.*.obat_id' => ['required', Rule::exists('erm_obat', 'id')->where('status_aktif', 1)],
            'obats.*.dosis' => 'required|numeric|gt:0',
        ], [
            'nama_paket.required' => 'Nama paket wajib diisi.',
            'nama_paket.max' => 'Nama paket maksimal 255 karakter.',
            'wadah_id.exists' => 'Wadah tidak ditemukan.',
            'bungkus_default.required' => 'Jumlah bungkus wajib diisi.',
            'bungkus_default.integer' => 'Jumlah bungkus harus bilangan bulat.',
            'bungkus_default.min' => 'Jumlah bungkus minimal 1.',
            'aturan_pakai_default.max' => 'Aturan pakai maksimal 255 karakter.',
            'is_active.boolean' => 'Status tidak valid.',
            'obats.required' => 'Minimal harus ada satu obat dalam paket.',
            'obats.min' => 'Minimal harus ada satu obat dalam paket.',
            'obats.*.obat_id.required' => 'Obat baris ke-:position wajib dipilih.',
            'obats.*.obat_id.exists' => 'Obat baris ke-:position sudah nonaktif atau tidak ditemukan di Master Obat, ganti dengan obat lain.',
            'obats.*.dosis.required' => 'Dosis baris ke-:position wajib diisi.',
            'obats.*.dosis.numeric' => 'Dosis baris ke-:position harus berupa angka.',
            'obats.*.dosis.gt' => 'Dosis baris ke-:position harus lebih dari 0.',
        ]);

        $obatIds = array_column($validated['obats'], 'obat_id');
        if (count($obatIds) !== count(array_unique($obatIds))) {
            throw ValidationException::withMessages(['obats' => 'Obat yang sama tidak boleh dimasukkan dua kali dalam satu paket.']);
        }

        PaketRacikan::assertUniqueComposition($validated['obats'], $exceptId);

        return $validated;
    }

    private function syncDetails(PaketRacikan $paket, array $obats): void
    {
        PaketRacikanDetail::where('paket_racikan_id', $paket->id)->delete();
        foreach ($obats as $obat) {
            PaketRacikanDetail::create([
                'paket_racikan_id' => $paket->id,
                'obat_id' => $obat['obat_id'],
                'dosis' => PaketRacikanDetail::normalizeDosis($obat['dosis']),
            ]);
        }
    }

    /**
     * Estimated harga of one bungkus, same proration as applying the paket in e-resep farmasi:
     * harga jual obat (harga_nonfornas) * dosis paket / dosis obat. Wadah is not included.
     */
    private function hargaPerBungkus(PaketRacikan $paket): float
    {
        return (float) $paket->details->sum(fn ($d) => ResepFarmasi::hargaRacikan($d->obat, $d->dosis));
    }
}
