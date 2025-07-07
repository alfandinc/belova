<?php

namespace App\Http\Controllers\ERM;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ERM\Obat;
use App\Models\ERM\Supplier;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\MetodeBayar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Obat::withInactive()->with(['zatAktifs', 'metodeBayar']);

            // Apply filters if provided
            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('kategori', $request->kategori);
            }

            if ($request->has('metode_bayar_id') && !empty($request->metode_bayar_id)) {
                $query->where('metode_bayar_id', $request->metode_bayar_id);
            }

            // Handle ordering
            if ($request->has('order') && !empty($request->order)) {
                foreach ($request->order as $order) {
                    $columnIndex = $order['column'];
                    $direction = $order['dir'];

                    // Map column index to actual column name
                    $columnName = $request->columns[$columnIndex]['name'];

                    if (!empty($columnName)) {
                        $query->orderBy($columnName, $direction);
                    }
                }
            } else {
                // Default ordering by stok ascending
                $query->orderBy('stok', 'asc');
            }

            return DataTables::of($query)
                ->addColumn('zat_aktif', function ($obat) {
                    $zats = [];
                    foreach ($obat->zatAktifs as $zat) {
                        $zats[] = '<span class="badge bg-secondary">' . $zat->nama . '</span>';
                    }
                    return implode(' ', $zats);
                })
                ->addColumn('action', function ($obat) {
                    $editBtn = '<a href="' . route('erm.obat.edit', $obat->id) . '" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<button data-id="' . $obat->id . '" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i></button>';
                    return $editBtn . ' ' . $deleteBtn;
                })
                ->rawColumns(['zat_aktif', 'action'])
                ->make(true);
        }

        $kategoris = Obat::select('kategori')->distinct()->pluck('kategori');
        $metodeBayars = MetodeBayar::all();

        return view('erm.obat.index', compact('kategoris', 'metodeBayars'));
    }

    public function create()
    {
        $obat = new Obat(); // Empty object for create case
        $zatAktif = ZatAktif::all();
        $supplier = Supplier::all();
        $metodeBayars = MetodeBayar::all();
        $kategoris = ['Antibiotik', 'Analgesik', 'Antipiretik', 'Antihistamin', 'Vitamin', 'Suplemen', 'Lainnya']; // Define your categories

        return view('erm.obat.create', compact('obat', 'zatAktif', 'supplier', 'metodeBayars', 'kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'kode_obat' => 'nullable|string',
            'dosis' => 'nullable|string',
            'satuan' => 'nullable|string',
            'zataktif_id' => 'nullable|array',
            'kategori' => 'nullable|string',
            'metode_bayar_id' => 'nullable|exists:erm_metode_bayar,id',
            'harga_net' => 'nullable|numeric',
            'harga_fornas' => 'nullable|numeric',
            'harga_nonfornas' => 'required|numeric',
            'stok' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $obat = Obat::updateOrCreate(
                ['id' => $request->id ?? null],
                [
                    'nama' => $request->nama,
                    'kode_obat' => $request->kode_obat,
                    'dosis' => $request->dosis,
                    'satuan' => $request->satuan,
                    'harga_net' => $request->harga_net,
                    'harga_fornas' => $request->harga_fornas,
                    'harga_nonfornas' => $request->harga_nonfornas,
                    'stok' => $request->stok ?? 0,
                    'kategori' => $request->kategori,
                    'metode_bayar_id' => $request->metode_bayar_id,
                    'status_aktif' => $request->has('status_aktif') ? 1 : 0,
                ]
            );

            // Sync zat aktif
            if ($request->has('zataktif_id') && !empty($request->zataktif_id)) {
                $obat->zatAktifs()->sync($request->zataktif_id);
            }

            DB::commit();

            $message = $request->id ? 'Obat berhasil diperbarui' : 'Obat berhasil ditambahkan';
            return redirect()->route('erm.obat.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan obat: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $obat = Obat::with('zatAktifs')->findOrFail($id);
        $zatAktif = ZatAktif::all();
        $supplier = Supplier::all();
        $metodeBayars = MetodeBayar::all();
        $kategoris = ['Obat', 'Produk', 'Racikan', 'Antihistamin', 'Lainnya'];

        return view('erm.obat.create', compact('obat', 'zatAktif', 'supplier', 'metodeBayars', 'kategoris'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        // Fetch obat data based on the search query and only show active medications
        $obats = Obat::where('status_aktif', 1)
            ->where(function($q) use ($query) {
                $q->where('nama', 'LIKE', "%{$query}%")
                  ->orWhere('dosis', 'LIKE', "%{$query}%")
                  ->orWhere('satuan', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get();

        // Return the data in Select2 format
        return response()->json($obats->map(function ($obat) {
            return [
                'id' => $obat->id,
                'text' => $obat->nama . ' - ' . $obat->dosis . ' ' . $obat->satuan,
                'nama' => $obat->nama,
                'dosis' => $obat->dosis,
                'satuan' => $obat->satuan,
                'stok' => $obat->stok,
                'harga_nonfornas' => $obat->harga_nonfornas,
            ];
        }));
    }

    public function destroy($id)
    {
        try {
            $obat = Obat::findOrFail($id);
            $obat->zatAktifs()->detach();
            $obat->delete();

            return response()->json(['success' => true, 'message' => 'Obat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus obat: ' . $e->getMessage()], 500);
        }
    }
}
