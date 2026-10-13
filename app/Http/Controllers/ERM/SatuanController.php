<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Satuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master Satuan: the options for satuan stok & dosis obat and sediaan produk R&D.
 * Data rows store the satuan as text, so rename rewrites them and delete is refused while one is in use.
 */
class SatuanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $satuans = Satuan::query()->orderBy('id')->get();
            $usage = Satuan::usageCounts();

            return response()->json([
                'data' => $satuans->map(fn (Satuan $satuan) => [
                    'id' => $satuan->id,
                    'nama' => $satuan->nama,
                    'untuk_stok' => $satuan->untuk_stok,
                    'untuk_dosis' => $satuan->untuk_dosis,
                    'dipakai' => $usage[strtolower($satuan->nama)] ?? 0,
                ]),
            ]);
        }

        return view('erm.satuan.index');
    }

    public function store(Request $request)
    {
        $satuan = Satuan::create($this->validated($request));

        return response()->json(['success' => true, 'message' => "Satuan \"{$satuan->nama}\" ditambahkan."]);
    }

    public function update(Request $request, Satuan $satuan)
    {
        $data = $this->validated($request, $satuan);

        $satuan->fill(['untuk_stok' => $data['untuk_stok'], 'untuk_dosis' => $data['untuk_dosis']]);
        $changed = $satuan->rename($data['nama']);

        return response()->json([
            'success' => true,
            'message' => $changed > 0
                ? "Satuan diperbarui; {$changed} data obat/produk ikut diganti ke \"{$satuan->nama}\"."
                : 'Satuan diperbarui.',
        ]);
    }

    public function destroy(Satuan $satuan)
    {
        $dipakai = $satuan->usageCount();
        if ($dipakai > 0) {
            return response()->json([
                'message' => "Satuan \"{$satuan->nama}\" masih dipakai di {$dipakai} data obat/produk, tidak bisa dihapus. Ganti namanya jika ejaannya salah.",
            ], 422);
        }

        $satuan->delete();

        return response()->json(['success' => true, 'message' => "Satuan \"{$satuan->nama}\" dihapus."]);
    }

    private function validated(Request $request, ?Satuan $satuan = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('erm_satuan', 'nama')->ignore($satuan?->id)],
            'untuk_stok' => 'sometimes|boolean',
            'untuk_dosis' => 'sometimes|boolean',
        ], [
            'nama.unique' => 'Satuan dengan nama ini sudah ada.',
        ]);
        $data['nama'] = trim(preg_replace('/\s+/', ' ', $data['nama']));
        $data['untuk_stok'] = $request->boolean('untuk_stok');
        $data['untuk_dosis'] = $request->boolean('untuk_dosis');

        return $data;
    }
}
