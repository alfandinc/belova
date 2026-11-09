<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Merchandise;
use App\Models\ERM\MerchandiseKartuStok;
use App\Models\ERM\MerchandisePembelian;
use App\Services\ERM\MerchandiseImageService;
use App\Services\ERM\MerchandiseStockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerchandisePembelianController extends Controller
{
    private const NOTA_FOLDER = 'merchandise/nota';

    private const NOTA_MAX_PX = 1600;

    public function __construct(private MerchandiseStockService $stock, private MerchandiseImageService $images)
    {
    }

    public function index($merchandiseId)
    {
        return response()->json($this->stock->purchaseHistory(Merchandise::findOrFail($merchandiseId)));
    }

    public function store(Request $request, $merchandiseId)
    {
        $merchandise = Merchandise::findOrFail($merchandiseId);
        $data = $this->validated($request, ['sumber', 'qty']);
        $data = $this->withNota($request, $data);

        $pembelian = $this->stock->receiveStock($merchandise->id, (int) $data['qty'], $data);

        return response()->json(['success' => true, 'message' => $this->savedMessage($pembelian), 'data' => $pembelian]);
    }

    public function update(Request $request, $id)
    {
        $pembelian = MerchandisePembelian::findOrFail($id);
        $request->merge(['sumber' => $pembelian->sumber]);
        $data = $this->validated($request);
        $oldNota = $pembelian->foto_nota;
        $data = $this->withNota($request, $data);

        $pembelian = $this->stock->updatePurchase($pembelian, $data);

        if (array_key_exists('foto_nota', $data)) {
            $this->images->delete($oldNota);
        }

        return response()->json(['success' => true, 'message' => $this->savedMessage($pembelian), 'data' => $pembelian]);
    }

    public function complete(Request $request, $kartuId)
    {
        $kartu = MerchandiseKartuStok::findOrFail($kartuId);
        $data = $this->validated($request, ['sumber']);
        $data = $this->withNota($request, $data);

        $pembelian = $this->stock->completeOpeningStock($kartu, $data);

        return response()->json(['success' => true, 'message' => $this->savedMessage($pembelian), 'data' => $pembelian]);
    }

    private function validated(Request $request, array $extra = []): array
    {
        $rules = [
            'sumber' => ['required', Rule::in([MerchandisePembelian::SUMBER_PEMBELIAN, MerchandisePembelian::SUMBER_HIBAH])],
            'tanggal_beli' => 'required|date|before_or_equal:today',
            'pemasok' => 'required_if:sumber,' . MerchandisePembelian::SUMBER_PEMBELIAN . '|nullable|string|max:255',
            'no_nota' => 'nullable|string|max:100',
            'harga_satuan' => 'required_if:sumber,' . MerchandisePembelian::SUMBER_PEMBELIAN . '|nullable|numeric|min:0|max:9999999999',
            'ongkir' => 'nullable|numeric|min:0|max:9999999999',
            'biaya_lain' => 'nullable|numeric|min:0|max:9999999999',
            'catatan' => 'nullable|string',
            'foto_nota' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        if (in_array('qty', $extra, true)) {
            $rules['qty'] = 'required|integer|min:1|max:100000';
        }

        $data = $request->validate($rules, [
            'tanggal_beli.required' => 'Tanggal beli wajib diisi.',
            'tanggal_beli.before_or_equal' => 'Tanggal beli tidak boleh di masa depan.',
            'pemasok.required_if' => 'Pemasok wajib diisi untuk pembelian.',
            'harga_satuan.required_if' => 'Harga satuan wajib diisi untuk pembelian.',
            'harga_satuan.min' => 'Harga satuan tidak boleh minus.',
            'ongkir.min' => 'Ongkir tidak boleh minus.',
            'biaya_lain.min' => 'Biaya lain tidak boleh minus.',
            'foto_nota.uploaded' => 'Foto nota gagal diunggah karena ukurannya terlalu besar.',
            'foto_nota.image' => 'Foto nota harus berupa gambar.',
            'foto_nota.mimes' => 'Foto nota harus berformat JPG, PNG, atau WEBP.',
            'foto_nota.max' => 'Ukuran foto nota maksimal 5 MB.',
        ]);

        unset($data['foto_nota']);

        return $data;
    }

    private function withNota(Request $request, array $data): array
    {
        if ($request->hasFile('foto_nota')) {
            $data['foto_nota'] = $this->images->store($request->file('foto_nota'), self::NOTA_FOLDER, self::NOTA_MAX_PX, 'foto_nota');
        }

        return $data;
    }

    private function savedMessage(MerchandisePembelian $pembelian): string
    {
        return 'Data ' . $pembelian->kode . ' berhasil disimpan.';
    }
}
