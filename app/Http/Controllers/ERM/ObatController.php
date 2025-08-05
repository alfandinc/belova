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
            // Debug the incoming status_aktif parameter
            \Illuminate\Support\Facades\Log::info('Obat status filter: ' . $request->status_aktif);
            
            // Always get all medications (both active and inactive)
            // This completely bypasses the global scope in the Obat model
            $query = Obat::withoutGlobalScope('active')->with(['zatAktifs', 'metodeBayar']);

            // Apply filters if provided
            if ($request->has('kategori') && !empty($request->kategori)) {
                $query->where('kategori', $request->kategori);
            }

            if ($request->has('metode_bayar_id') && !empty($request->metode_bayar_id)) {
                $query->where('metode_bayar_id', $request->metode_bayar_id);
            }
            
            // Filter by status if provided
            if ($request->filled('status_aktif')) {
                \Illuminate\Support\Facades\Log::info('Applying status filter: ' . $request->status_aktif);
                $query->where('status_aktif', $request->status_aktif);
            } else {
                \Illuminate\Support\Facades\Log::info('No status filter applied, showing all medications');
            }
            // When no status filter is applied or empty string is passed, 
            // we want to show all medications (both active and inactive)

                // Always use a subquery to provide min_exp_date for ordering and counting
                $obatTable = (new \App\Models\ERM\Obat)->getTable();
                $sub = \App\Models\ERM\Obat::withoutGlobalScope('active')
                    ->leftJoin('erm_fakturbeli_items as fbi', $obatTable.'.id', '=', 'fbi.obat_id')
                    ->select($obatTable.'.*', DB::raw('MIN(fbi.expiration_date) as min_exp_date'))
                    ->groupBy($obatTable.'.id');

                $query = DB::query()->fromSub($sub, $obatTable);

                // Apply filters if provided
                if ($request->has('kategori') && !empty($request->kategori)) {
                    $query->where('kategori', $request->kategori);
                }
                if ($request->has('metode_bayar_id') && !empty($request->metode_bayar_id)) {
                    $query->where('metode_bayar_id', $request->metode_bayar_id);
                }
                if ($request->filled('status_aktif')) {
                    $query->where('status_aktif', $request->status_aktif);
                }

                // Handle ordering
                if ($request->has('order') && !empty($request->order)) {
                    foreach ($request->order as $order) {
                        $columnIndex = $order['column'];
                        $direction = $order['dir'];
                        $columnName = $request->columns[$columnIndex]['name'];
                        if ($columnName === 'min_exp_date') {
                            // NULLs last for ascending, NULLs first for descending
                            if (strtolower($direction) === 'asc') {
                                $query->orderByRaw('min_exp_date IS NULL, min_exp_date ASC');
                            } else {
                                $query->orderByRaw('min_exp_date IS NOT NULL, min_exp_date DESC');
                            }
                        } else if (!empty($columnName)) {
                            $query->orderBy($columnName, $direction);
                        }
                    }
                } else {
                    // Default: NULLs last
                    $query->orderByRaw('min_exp_date IS NULL, min_exp_date ASC');
                }

                // Eager load relationships after subquery
                $obatIds = $query->pluck('id');
                $obatModels = \App\Models\ERM\Obat::with(['zatAktifs', 'metodeBayar'])->whereIn('id', $obatIds)->get()->keyBy('id');

                return DataTables::of($query)
                    ->addColumn('zat_aktif', function ($obat) use ($obatModels) {
                        $model = $obatModels[$obat->id] ?? null;
                        $zats = [];
                        if ($model) {
                            foreach ($model->zatAktifs as $zat) {
                                $zats[] = '<span class="badge bg-secondary">' . $zat->nama . '</span>';
                            }
                        }
                        return implode(' ', $zats);
                    })
                    ->addColumn('batch_info', function ($obat) {
                        $items = \App\Models\ERM\FakturBeliItem::where('obat_id', $obat->id)
                            ->orderBy('expiration_date', 'asc')
                            ->get(['batch', 'expiration_date', 'sisa']);
                        $data = $items->map(function($item) {
                            return [
                                'batch' => $item->batch,
                                'expiration_date' => $item->expiration_date,
                                'sisa' => $item->sisa
                            ];
                        });
                        return '<button class="btn btn-sm btn-secondary batch-info-btn" data-batchinfo="' . htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8') . '"><i class="fas fa-list"></i> Batch</button>';
                    })
                    ->addColumn('status_aktif', function ($obat) {
                        return $obat->status_aktif;
                    })
                    ->addColumn('action', function ($obat) {
                        $editBtn = '<a href="' . route('erm.obat.edit', $obat->id) . '" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>';
                        $deleteBtn = '<button data-id="' . $obat->id . '" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i></button>';
                        return $editBtn . ' ' . $deleteBtn;
                    })
                    ->rawColumns(['zat_aktif', 'action', 'batch_info'])
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
        // Debug: Log the request parameters
        \Illuminate\Support\Facades\Log::info('Obat store/update request:', [
            'has_status_aktif' => $request->has('status_aktif'),
            'status_aktif_value' => $request->input('status_aktif'),
            'all_inputs' => $request->all()
        ]);
        
        $request->validate([
            'nama' => 'required|string',
            'kode_obat' => 'nullable|string',
            'dosis' => 'nullable|string',
            'satuan' => 'nullable|string',
            // zataktif_id is optional, no validation required
            'kategori' => 'nullable|string',
            'metode_bayar_id' => 'nullable|exists:erm_metode_bayar,id',
            'harga_net' => 'nullable|numeric',
            'harga_fornas' => 'nullable|numeric',
            'harga_nonfornas' => 'required|numeric',
            'stok' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Log the ID being used for update
            \Illuminate\Support\Facades\Log::info('Obat update/create with ID: ' . ($request->id ?? 'null'));
            
            // The status_aktif value to be used
            $statusAktif = ($request->has('status_aktif_submitted') && $request->has('status_aktif')) ? 1 : 0;
            
            // Check if we're updating an existing record or creating a new one
            if ($request->filled('id')) {
                // Update existing record using find + update
                $obat = Obat::withInactive()->findOrFail($request->id);
                $obat->update([
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
                    'status_aktif' => $statusAktif,
                ]);
            } else {
                // Create new record
                $obat = Obat::create([
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
                    'status_aktif' => $statusAktif,
                ]);
            }

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
        $obat = Obat::withInactive()->with('zatAktifs')->findOrFail($id);
        
        // Debug: Log the obat status when loading edit form
        \Illuminate\Support\Facades\Log::info('Obat edit loaded:', [
            'id' => $obat->id,
            'name' => $obat->nama,
            'status_aktif' => $obat->status_aktif
        ]);
        
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
            $obat = Obat::withInactive()->findOrFail($id);
            $obat->zatAktifs()->detach();
            $obat->delete();

            return response()->json(['success' => true, 'message' => 'Obat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus obat: ' . $e->getMessage()], 500);
        }
    }
}
