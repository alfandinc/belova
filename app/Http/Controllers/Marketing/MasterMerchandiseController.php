<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\ERM\Merchandise;
use App\Services\ERM\MerchandiseImageService;
use App\Services\ERM\MerchandiseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterMerchandiseController extends Controller
{
    private const IMAGE_FOLDER = 'merchandise';

    private const IMAGE_MAX_PX = 300;

    public function __construct(private MerchandiseStockService $stock, private MerchandiseImageService $images)
    {
    }

    private function canManage(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(MerchandiseStockService::MANAGE_ROLES);
    }

    public function index(Request $request)
    {
        $canManage = $this->canManage();

        return view('marketing.master_merchandise.index', [
            'canManage' => $canManage,
            'kliniks' => Klinik::orderBy('id')->get(['id', 'nama'])
                ->map(fn (Klinik $k) => ['id' => $k->id, 'nama' => Klinik::shortName($k->nama)]),
            'pemasokList' => $canManage ? $this->stock->pemasokList() : collect(),
        ]);
    }

    // Data for yajra DataTable
    public function data(Request $request)
    {
        $klinikId = $request->query('klinik_id');
        $query = Merchandise::query()
            ->with('kliniks:id,nama')
            ->when($request->query('status') === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when($klinikId, fn ($q) => $q->whereHas('kliniks', fn ($k) => $k->where('erm_klinik.id', $klinikId)));
        $canManage = $this->canManage();
        $totalKlinik = Klinik::count();

        return datatables()->of($query)
            ->addColumn('thumb', function ($row) {
                return $row->image_url
                    ? '<img src="'.e($row->image_url).'" class="merch-thumb" alt="'.e($row->name).'">'
                    : '<span class="merch-thumb is-empty" title="Belum ada gambar"><i class="mdi mdi-image-off-outline"></i></span>';
            })
            ->addColumn('item', function ($row) {
                return '<div class="font-weight-bold">'.e($row->name).'</div>'
                    . ($row->description ? '<div class="small merch-muted">'.e($row->description).'</div>' : '');
            })
            ->addColumn('klinik_label', function ($row) use ($totalKlinik) {
                $label = $row->klinikLabel($totalKlinik);
                $class = $label === 'Milik bersama' ? 'badge-soft-info' : ($label === 'Belum diatur' ? 'badge-soft-danger' : 'badge-soft-primary');

                return '<span class="badge '.$class.' merch-klinik-badge">'.e($label).'</span>';
            })
            ->addColumn('price_label', function ($row) use ($canManage) {
                if (!$canManage) {
                    return null;
                }

                $price = $row->latestBuyPrice();

                return $price === null ? '-' : 'Rp '.number_format($price, 0, ',', '.');
            })
            ->addColumn('current_stock', function ($row) {
                return $this->stock->currentStock($row->id);
            })
            ->addColumn('actions', function ($row) use ($canManage) {
                $buttons = '<button type="button" class="btn btn-sm btn-info btn-history" data-id="'.$row->id.'" data-name="'.e($row->name).'">Riwayat</button> ';

                if (!$canManage) {
                    return $buttons;
                }

                if ($row->is_active) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-success btn-stock" data-id="'.$row->id.'" data-name="'.e($row->name).'">Stok Masuk</button> ';
                }

                $buttons .= '<button type="button" class="btn btn-sm btn-warning btn-edit" data-id="'.$row->id.'">Edit</button> '
                    . '<button type="button" class="btn btn-sm btn-secondary btn-toggle-active" data-id="'.$row->id.'" data-active="'.($row->is_active ? 1 : 0).'">'.($row->is_active ? 'Nonaktifkan' : 'Aktifkan').'</button> ';

                if (!$row->hasHistory()) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'">Hapus</button>';
                }

                return $buttons;
            })
            ->removeColumn('price')
            ->rawColumns(['thumb', 'item', 'klinik_label', 'actions'])
            ->make(true);
    }

    public function edit($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);
        return response()->json($m->toArray() + ['klinik_ids' => $m->kliniks()->pluck('erm_klinik.id')]);
    }

    public function stockHistory($id)
    {
        $m = Merchandise::find($id);
        if (!$m) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'merchandise' => [
                'id' => $m->id,
                'kode' => $m->kode,
                'name' => $m->name,
            ],
            'data' => $this->stock->history($m),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMaster($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), self::IMAGE_FOLDER, self::IMAGE_MAX_PX, 'image');
        }

        $klinikIds = $data['klinik_ids'];
        unset($data['klinik_ids']);

        $m = DB::transaction(function () use ($data, $klinikIds) {
            $m = Merchandise::create($data);
            $m->kliniks()->sync($klinikIds);

            return $m;
        });

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function update(Request $request, $id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);

        $data = $this->validateMaster($request);
        $oldImage = $m->image;

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), self::IMAGE_FOLDER, self::IMAGE_MAX_PX, 'image');
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        $klinikIds = $data['klinik_ids'];
        unset($data['klinik_ids']);

        DB::transaction(function () use ($m, $data, $klinikIds) {
            $m->update($data);
            $m->kliniks()->sync($klinikIds);
        });

        if (array_key_exists('image', $data)) {
            $this->images->delete($oldImage);
        }

        return response()->json(['success' => true, 'data' => $m]);
    }

    public function toggleActive($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);

        $m->update(['is_active' => !$m->is_active]);

        return response()->json(['success' => true, 'is_active' => $m->is_active]);
    }

    public function destroy($id)
    {
        $m = Merchandise::find($id);
        if (!$m) return response()->json(['error' => 'Not found'], 404);

        if ($m->hasHistory()) {
            return response()->json([
                'success' => false,
                'message' => 'Merchandise sudah punya riwayat, jadi tidak bisa dihapus. Gunakan Nonaktifkan.',
            ], 422);
        }

        $image = $m->image;
        $m->delete();
        $this->images->delete($image);

        return response()->json(['success' => true]);
    }

    public function opname(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|distinct|exists:erm_merchandises,id',
            'items.*.fisik' => 'required|integer|min:0|max:100000',
            'items.*.stok_sistem' => 'required|integer',
            'items.*.alasan' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Isi stok fisik minimal satu merchandise.',
            'items.*.fisik.min' => 'Stok fisik tidak boleh minus.',
            'items.*.fisik.max' => 'Stok fisik terlalu besar.',
        ]);

        $result = $this->stock->applyOpname($data['items']);

        return response()->json([
            'success' => true,
            'message' => $result['counted'] . ' item dihitung, ' . $result['changed'] . ' item ada selisih dan stoknya sudah disesuaikan.',
        ]);
    }

    private function validateMaster(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'klinik_ids' => 'required|array|min:1',
            'klinik_ids.*' => 'integer|distinct|exists:erm_klinik,id',
        ], [
            'klinik_ids.required' => 'Pilih minimal satu klinik pemilik.',
            'klinik_ids.min' => 'Pilih minimal satu klinik pemilik.',
            'image.uploaded' => 'Gambar gagal diunggah karena ukurannya terlalu besar. Coba file yang lebih kecil.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        unset($data['image']);

        return $data;
    }
}
