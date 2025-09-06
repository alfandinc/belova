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
    /**
     * Update harga_nonfornas (harga jual) via AJAX.
     */
    public function updateHargaJual(Request $request, $id)
    {
        $request->validate([
            'harga_nonfornas' => 'required|numeric|min:0',
        ]);
        try {
            $obat = Obat::withInactive()->findOrFail($id);
            $obat->harga_nonfornas = $request->harga_nonfornas;
            $obat->save();
            return response()->json(['success' => true, 'message' => 'Harga jual berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui harga jual: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Display the Monitor Profit page with DataTable.
     */
    public function monitorProfit(Request $request)
    {
        if ($request->ajax()) {
            $PPN = 11;
            $obats = Obat::withInactive()
                ->select(['id', 'kode_obat', 'nama', 'hpp', 'hpp_jual', 'harga_nonfornas'])
                // profit_percent_value: profit sebelum PPN
                ->selectRaw('(CASE WHEN hpp_jual > 0 THEN (((harga_nonfornas / (1 + '.$PPN.'/100)) - hpp_jual) / hpp_jual) * 100 ELSE NULL END) as profit_percent_value')
                // profit_percent_setelah_ppn: profit setelah PPN
                ->selectRaw('(CASE WHEN hpp_jual > 0 THEN (((harga_nonfornas - hpp_jual) / hpp_jual) * 100) ELSE NULL END) as profit_percent_setelah_ppn');
            $defaultProfit = 30; // Default profit percent
            return DataTables::of($obats)
                ->addColumn('hpp_jual', function ($obat) {
                    return number_format($obat->hpp_jual, 0);
                })
                ->addColumn('profit_percent', function ($obat) {
                    if (isset($obat->profit_percent_value)) {
                        $percent = $obat->profit_percent_value;
                        $text = number_format($percent, 2) . '%';
                        if ($percent < 30) {
                            $text .= ' <span class="text-warning blink-warning" title="Profit di bawah 30%"><i class="fas fa-exclamation-triangle"></i></span>';
                        }
                        return $text;
                    }
                    return '-';
                })
                ->addColumn('profit_percent_setelah_ppn', function ($obat) {
                    if (isset($obat->profit_percent_setelah_ppn)) {
                        return number_format($obat->profit_percent_setelah_ppn, 2) . '%';
                    }
                    return '-';
                })
                ->addColumn('saran_harga_jual', function ($obat) use ($PPN, $defaultProfit) {
                    $hpp_jual = floatval($obat->hpp_jual);
                    $profitPercent = $defaultProfit;
                    $saran = $hpp_jual * ((100 + $profitPercent) / 100) * ((100 + $PPN) / 100);
                    return $hpp_jual > 0 ? number_format($saran, 0) : '-';
                })
                ->orderColumn('profit_percent', 'profit_percent_value $1')
                ->editColumn('hpp', function ($obat) {
                    return number_format($obat->hpp, 0);
                })
                ->editColumn('harga_nonfornas', function ($obat) {
                    return number_format($obat->harga_nonfornas, 0);
                })
                ->addColumn('aksi', function ($obat) {
                    $btn = '<button type="button" class="btn btn-sm btn-warning btn-edit-harga" data-id="'.$obat->id.'" data-nama="'.e($obat->nama).'" data-harga="'.$obat->harga_nonfornas.'"><i class="fas fa-edit"></i> Edit</button>';
                    return $btn;
                })
                ->rawColumns(['aksi', 'profit_percent'])
                ->make(true);
        }
        return view('erm.obat.monitor_profit');
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Log the status filter being used
            if ($request->filled('status_aktif')) {
                \Illuminate\Support\Facades\Log::info('Status filter applied:', ['status' => $request->status_aktif]);
            } else {
                \Illuminate\Support\Facades\Log::info('No status filter applied, showing all medications');
            }

            // Simple query without batch/expiration complexity
            $query = \App\Models\ERM\Obat::withoutGlobalScope('active')
                ->with(['zatAktifs', 'metodeBayar']);

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

            return DataTables::of($query)
                ->addColumn('metode_bayar', function ($obat) {
                    return $obat->metodeBayar ? $obat->metodeBayar->nama : '-';
                })
                ->addColumn('zat_aktif', function ($obat) {
                    $zats = [];
                    foreach ($obat->zatAktifs as $zat) {
                        $zats[] = '<span class="badge badge-zat-aktif">' . $zat->nama . '</span>';
                    }
                    return implode(' ', $zats);
                })
                // Add warning icon if dosis or satuan is null
                ->editColumn('nama', function ($obat) {
                    $warning = '';
                    if (empty($obat->dosis) || empty($obat->satuan)) {
                        $warning = '<span class="text-warning" style="margin-left:5px;" title="Dosis atau satuan belum diisi"><i class="fas fa-exclamation-triangle" style="color:orange;"></i></span>';
                    }
                    return e($obat->nama) . $warning;
                })
                ->addColumn('status_aktif', function ($obat) {
                    return $obat->status_aktif;
                })
                ->addColumn('action', function ($obat) {
                    $editBtn = '<button type="button" class="btn btn-sm btn-info btn-edit-obat" data-id="' . $obat->id . '"><i class="fas fa-edit"></i></button>';
                    $deleteBtn = '<button data-id="' . $obat->id . '" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i></button>';
                    return $editBtn . ' ' . $deleteBtn;
                })
                ->rawColumns(['zat_aktif', 'action', 'nama'])
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
            'kategori' => 'nullable|string',
            'metode_bayar_id' => 'nullable|exists:erm_metode_bayar,id',
            'harga_net' => 'nullable|numeric',
            'harga_fornas' => 'nullable|numeric',
            'harga_nonfornas' => 'required|numeric',
            'stok' => 'nullable|integer|min:0',
            'hpp' => 'nullable|numeric',
            'hpp_jual' => 'nullable|numeric',
        ]);

        DB::beginTransaction();

        try {
            // Log the ID being used for update
            \Illuminate\Support\Facades\Log::info('Obat update/create with ID: ' . ($request->id ?? 'null'));
            
            // Debug: Log the status_aktif value received
            \Illuminate\Support\Facades\Log::info('Status aktif received: ' . $request->input('status_aktif'));
            
            // The status_aktif value to be used - directly from the request
            $statusAktif = $request->input('status_aktif', 1); // Default to 1 (active) if not provided
            
            \Illuminate\Support\Facades\Log::info('Status aktif processed: ' . $statusAktif);
            
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
                    'hpp' => $request->hpp,
                    'hpp_jual' => $request->hpp_jual,
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
                    'hpp' => $request->hpp,
                    'hpp_jual' => $request->hpp_jual,
                ]);
            }

            // Sync zat aktif
            if ($request->has('zataktif_id') && !empty($request->zataktif_id)) {
                $obat->zatAktifs()->sync($request->zataktif_id);
            }

            DB::commit();

            $message = $request->id ? 'Obat berhasil diperbarui' : 'Obat berhasil ditambahkan';
                if ($request->ajax()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
            return redirect()->route('erm.obat.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Gagal menyimpan obat: ' . $e->getMessage()], 500);
                }
            // return redirect()->back()->with('error', 'Gagal menyimpan obat: ' . $e->getMessage());
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
        
        // If this is an AJAX request, return JSON data for the modal
        if (request()->ajax()) {
            return response()->json([
                'id' => $obat->id,
                'kode_obat' => $obat->kode_obat,
                'nama' => $obat->nama,
                'hpp' => $obat->hpp,
                'hpp_jual' => $obat->hpp_jual,
                'harga_net' => $obat->harga_net,
                'harga_nonfornas' => $obat->harga_nonfornas,
                'metode_bayar_id' => $obat->metode_bayar_id,
                'kategori' => $obat->kategori,
                'zataktif_id' => $obat->zatAktifs->pluck('id')->toArray(),
                'dosis' => $obat->dosis,
                'satuan' => $obat->satuan,
                'status_aktif' => $obat->status_aktif,
                'stok' => $obat->stok
            ]);
        }
        
        // For regular requests, return the view (for non-modal edit page)
        $zatAktif = ZatAktif::all();
        $supplier = Supplier::all();
        $metodeBayars = MetodeBayar::all();
        $kategoris = ['Obat', 'Produk', 'Racikan', 'Antihistamin', 'Lainnya'];

        return view('erm.obat.create', compact('obat', 'zatAktif', 'supplier', 'metodeBayars', 'kategoris'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $metodeBayarId = $request->get('metode_bayar_id');

        // Search by obat name, dosis, satuan, or zat aktif name, and filter by metode_bayar_id if provided
        $obatsQuery = Obat::where('status_aktif', 1)
            ->where(function($q) use ($query) {
                $q->where('nama', 'LIKE', "%{$query}%")
                  ->orWhere('dosis', 'LIKE', "%{$query}%")
                  ->orWhere('satuan', 'LIKE', "%{$query}%")
                  ->orWhereHas('zatAktifs', function($z) use ($query) {
                      $z->where('nama', 'LIKE', "%{$query}%");
                  });
            });
        if ($metodeBayarId) {
            $obatsQuery->where('metode_bayar_id', $metodeBayarId);
        }
        $obats = $obatsQuery->limit(10)->get();

        // Return the data in Select2 format (with 'results' key)
        $results = $obats->map(function ($obat) {
            // Show zat aktif names in the text for better UX, format to title case
            $zatAktifNames = $obat->zatAktifs->pluck('nama')->map(function($nama) {
                // Convert to lowercase then uppercase first letter of each word
                return ucwords(strtolower($nama));
            })->implode(', ');
            $text = $obat->nama;
            if ($zatAktifNames) {
                $text .= ' [' . $zatAktifNames . ']';
            }
            $text .= ' - ' . $obat->dosis . ' ' . $obat->satuan;
            return [
                'id' => $obat->id,
                'text' => $text,
                'nama' => $obat->nama,
                'dosis' => $obat->dosis,
                'satuan' => $obat->satuan,
                'stok' => $obat->stok,
                'harga_nonfornas' => $obat->harga_nonfornas,
            ];
        })->values();
        return response()->json(['results' => $results]);
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

    /**
     * Export Obat data to Excel
     */
    public function exportExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ObatExport($request), 'data_obat.xlsx');
    }
}
