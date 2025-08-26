<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Tindakan;
use App\Models\ERM\PaketTindakan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TindakanController extends Controller
{
    /**
     * Get before/after gallery for tindakan.
     */
    public function galeriBeforeAfter($id)
    {
        $informConsents = \App\Models\ERM\InformConsent::where('tindakan_id', $id)
            ->whereNotNull('before_image_path')
            ->where('before_image_path', '!=', '')
            ->whereNotNull('after_image_path')
            ->where('after_image_path', '!=', '')
            ->with(['visitation.pasien', 'visitation.dokter'])
            ->get();

        $result = [];
        foreach ($informConsents as $ic) {
            // If path does not start with 'storage/', prepend it
            $beforePath = $ic->before_image_path;
            $afterPath = $ic->after_image_path;
            if ($beforePath && !preg_match('/^storage\//', $beforePath)) {
                $beforePath = 'storage/' . ltrim($beforePath, '/');
            }
            if ($afterPath && !preg_match('/^storage\//', $afterPath)) {
                $afterPath = 'storage/' . ltrim($afterPath, '/');
            }
            $tanggalVisit = $ic->visitation && $ic->visitation->tanggal_visitation
                ? \Carbon\Carbon::parse($ic->visitation->tanggal_visitation)->translatedFormat('j F Y')
                : 'N/A';
            $result[] = [
                'nama_tindakan' => $ic->tindakan ? $ic->tindakan->nama : 'Tindakan',
                'pasien_nama' => $ic->visitation && $ic->visitation->pasien ? $ic->visitation->pasien->nama : 'N/A',
                'tanggal_visit' => $tanggalVisit,
                'dokter_nama' => $ic->visitation && $ic->visitation->dokter ? $ic->visitation->dokter->user ? $ic->visitation->dokter->user->name : ($ic->visitation->dokter->nama ?? 'N/A') : 'N/A',
                'before_image' => asset($beforePath),
                'after_image' => asset($afterPath),
                'allow_post' => (bool) $ic->allow_post,
            ];
        }
        return response()->json($result);
    }
    /**
     * Display a listing of tindakan.
     */
    public function index()
    {
        return view('marketing.tindakan.index');
    }

    /**
     * Get tindakan data for DataTables.
     */
    public function getTindakanData(Request $request)
    {
        $tindakan = Tindakan::with('spesialis')->withCount('sop')->get();

        return DataTables::of($tindakan)
            ->addColumn('spesialis_nama', function ($row) {
                return $row->spesialis ? $row->spesialis->nama : 'N/A';
            })
            ->editColumn('nama', function ($row) {
                $nama = $row->nama;
                if ($row->sop_count == 0) {
                    $nama = '<i class="fas fa-exclamation-triangle text-warning blink-icon mr-2" title="No SOP available"></i>' . $nama;
                }
                return $nama;
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-primary btn-sm edit-tindakan" data-id="'.$row->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-tindakan" data-id="'.$row->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" class="btn btn-info btn-sm galeri-before-after" data-id="'.$row->id.'">
                        <i class="fas fa-images"></i> Galeri Before After
                    </button>
                ';
            })
            ->rawColumns(['nama', 'action'])
            ->make(true);
    }

        /**
     * AJAX search for SOPs (for Select2)
     */
    public function searchSop(Request $request)
    {
        $q = $request->input('q');
        $sopQuery = \App\Models\ERM\Sop::query();
        if ($q) {
            $sopQuery->where('nama_sop', 'like', "%".$q."%");
        }
        $sops = $sopQuery->orderBy('nama_sop')->limit(20)->get(['id', 'nama_sop']);
        $results = $sops->map(function($sop) {
            return [
                'id' => $sop->id,
                'nama_sop' => $sop->nama_sop
            ];
        });
        return response()->json($results);
    }

    /**
     * Get all SOPs and selected SOPs for a tindakan (for modal select2)
     */
    public function getSopTindakan($id)
    {
        $tindakan = Tindakan::with(['sop' => function($q) { $q->orderBy('urutan'); }])->findOrFail($id);
        $orderedSops = $tindakan->sop;
        $allSop = \App\Models\ERM\Sop::all(['id', 'nama_sop']);
        $selectedSopIds = $orderedSops->pluck('id')->toArray();
        return response()->json([
            'all_sop' => $allSop,
            'selected_sop_ids' => $selectedSopIds
        ]);
    }

    /**
     * Update SOPs for a tindakan (from modal select2)
     */
    public function updateSopTindakan(Request $request, $id)
    {
        $tindakan = Tindakan::findOrFail($id);
        $sopIds = $request->input('sop_ids', []);
        $currentSops = $tindakan->sop;
        $toDelete = $currentSops->whereNotIn('id', $sopIds);
        $notDeleted = [];
        foreach ($toDelete as $sop) {
            $isReferenced = DB::table('erm_spk_details')->where('sop_id', $sop->id)->exists();
            if ($isReferenced) {
                $notDeleted[] = $sop->nama_sop;
                // Do not delete or update, and keep it assigned to tindakan (do nothing)
                if (!in_array($sop->id, $sopIds)) {
                    $sopIds[] = $sop->id;
                }
                continue;
            }
            // Only delete if not referenced
            $sop->delete();
        }
        // Do NOT update tindakan_id to null for referenced SOPs, only for non-referenced ones
        // So, no further detach/update here
        // Add new SOPs (clone from master SOP if not already present)
        $existingIds = $currentSops->pluck('id')->toArray();
        foreach ($sopIds as $idx => $sopId) {
            if (!in_array($sopId, $existingIds)) {
                $sop = \App\Models\ERM\Sop::find($sopId);
                if ($sop) {
                    $tindakan->sop()->create([
                        'nama_sop' => $sop->nama_sop,
                        'deskripsi' => $sop->deskripsi,
                        'urutan' => $idx + 1
                    ]);
                }
            }
        }
        // Update urutan for all assigned SOPs (including existing)
        $assignedSops = $tindakan->sop()->whereIn('id', $sopIds)->get();
        foreach ($sopIds as $idx => $sopId) {
            $sop = $assignedSops->where('id', $sopId)->first();
            if ($sop) {
                $sop->urutan = $idx + 1;
                $sop->save();
            }
        }
        $msg = 'SOP tindakan updated successfully.';
        if (count($notDeleted)) {
            $msg .= ' Some SOPs were not deleted because they are in use: ' . implode(', ', $notDeleted);
        }
        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    /**
     * Show the form for creating a new tindakan.
     */
    public function create()
    {
        return view('marketing.tindakan.form');
    }

    /**
     * Store a newly created tindakan or update an existing one.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:erm_tindakan,id',
            'nama' => ['required', 'string', 'max:255', 
                Rule::unique('erm_tindakan')->ignore($request->id)],
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric|min:0',
            'spesialis_id' => 'required|exists:erm_spesialisasis,id',
            'obat_ids' => 'array',
            'obat_ids.*' => 'exists:erm_obat,id',
        ]);

        try {
            DB::beginTransaction();
            $tindakan = Tindakan::updateOrCreate(
                ['id' => $request->id],
                [
                    'nama' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga' => $request->harga,
                    'spesialis_id' => $request->spesialis_id,
                ]
            );

            // Handle SOPs (from text input, as names)
            $sopNames = $request->input('sop_names');
            $sopNamesArr = [];
            if ($sopNames) {
                $sopNamesArr = is_array($sopNames) ? $sopNames : explode(',', $sopNames);
                $sopNamesArr = array_filter(array_map('trim', $sopNamesArr)); // Remove empty strings
            }
            
            // Get current SOPs for tindakan
            $currentSops = $tindakan->sop()->get();
            
            // Find SOPs to delete (current SOPs not in the new list)
            $toDelete = $currentSops->filter(function($sop) use ($sopNamesArr) {
                return !in_array($sop->nama_sop, $sopNamesArr);
            });
            
            // Only delete SOPs not referenced in erm_spk_details
            foreach ($toDelete as $sop) {
                $isReferenced = DB::table('erm_spk_details')->where('sop_id', $sop->id)->exists();
                if (!$isReferenced) {
                    $sop->delete();
                }
            }
            
            // Add or update SOPs
            foreach ($sopNamesArr as $idx => $sopName) {
                $sopName = trim($sopName);
                if ($sopName === '') continue;
                $existing = $currentSops->firstWhere('nama_sop', $sopName);
                if ($existing) {
                    // Update order if needed
                    if ($existing->urutan != $idx + 1) {
                        $existing->urutan = $idx + 1;
                        $existing->save();
                    }
                } else {
                    $tindakan->sop()->create([
                        'nama_sop' => $sopName,
                        'deskripsi' => null,
                        'urutan' => $idx + 1
                    ]);
                }
            }

            // Sync bundled obat
            $obatIds = $request->input('obat_ids', []);
            $tindakan->obats()->sync($obatIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan has been ' . ($request->id ? 'updated' : 'created') . ' successfully!',
                'data' => $tindakan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tindakan data by ID.
     */
    public function getTindakan($id)
    {
        $tindakan = Tindakan::with(['spesialis', 'sop' => function($q) { $q->orderBy('urutan'); }, 'obats'])->findOrFail($id);
        // Return SOPs as array for JS
        $result = $tindakan->toArray();
        $result['sop'] = $tindakan->sop->map(function($sop) {
            return [
                'nama_sop' => $sop->nama_sop,
                'urutan' => $sop->urutan
            ];
        })->toArray();
        $result['obat_ids'] = $tindakan->obats->pluck('id')->toArray();
        return response()->json($result);
    }

    /**
     * Delete a tindakan.
     */
    public function destroy($id)
    {
        try {
            $tindakan = Tindakan::findOrFail($id);
            
            // Check if tindakan is used in any paket
            $isUsed = $tindakan->paketTindakan()->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete tindakan because it is included in one or more paket tindakan.'
                ], 400);
            }
            
            // Check if tindakan has any inform consents
            if ($tindakan->informConsent()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete tindakan because it has associated inform consents.'
                ], 400);
            }
            
            $tindakan->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tindakan deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of paket tindakan.
     */
    public function indexPaket()
    {
        return view('marketing.tindakan.paket-index');
    }

    /**
     * Get paket tindakan data for DataTables.
     */
    public function getPaketData(Request $request)
    {
        $paketTindakan = PaketTindakan::with('tindakan');

        return DataTables::of($paketTindakan)
            ->addColumn('tindakan_list', function ($row) {
                $tindakanNames = $row->tindakan->pluck('nama')->toArray();
                return implode(', ', $tindakanNames);
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-primary btn-sm edit-paket" data-id="'.$row->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-paket" data-id="'.$row->id.'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new paket tindakan.
     */
    public function createPaket()
    {
        $tindakan = Tindakan::all();
        return view('marketing.tindakan.paket-form', compact('tindakan'));
    }

    /**
     * Store a newly created paket tindakan or update an existing one.
     */
    public function storePaket(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:erm_paket_tindakan,id',
            'nama' => ['required', 'string', 'max:255', 
                Rule::unique('erm_paket_tindakan')->ignore($request->id)],
            'deskripsi' => 'nullable|string',
            'harga_paket' => 'required|numeric|min:0',
            'tindakan_ids' => 'required|array|min:1',
            'tindakan_ids.*' => 'exists:erm_tindakan,id',
        ]);

        try {
            DB::beginTransaction();
            
            $paketTindakan = PaketTindakan::updateOrCreate(
                ['id' => $request->id],
                [
                    'nama' => $request->nama,
                    'deskripsi' => $request->deskripsi,
                    'harga_paket' => $request->harga_paket,
                ]
            );

            // Sync the tindakan relationships
            $paketTindakan->tindakan()->sync($request->tindakan_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paket Tindakan has been ' . ($request->id ? 'updated' : 'created') . ' successfully!',
                'data' => $paketTindakan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paket tindakan data by ID.
     */
    public function getPaket($id)
    {
        $paketTindakan = PaketTindakan::with('tindakan')->findOrFail($id);
        $tindakanIds = $paketTindakan->tindakan->pluck('id')->toArray();
        
        $response = [
            'paket' => $paketTindakan,
            'tindakan_ids' => $tindakanIds
        ];
        
        return response()->json($response);
    }

    /**
     * Delete a paket tindakan.
     */
    public function destroyPaket($id)
    {
        try {
            $paketTindakan = PaketTindakan::findOrFail($id);
            
            // Check if paket has any billings
            if ($paketTindakan->billing()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete paket tindakan because it has associated billings.'
                ], 400);
            }
            
            // Delete the relationship with tindakan
            $paketTindakan->tindakan()->detach();
            
            // Delete the paket
            $paketTindakan->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Paket Tindakan deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of specialists for dropdown.
     */
    public function getSpesialisasiList()
    {
        $spesialisasi = \App\Models\ERM\Spesialisasi::select('id', 'nama')->get();
        return response()->json($spesialisasi);
    }

    /**
     * Get list of tindakan for dropdown.
     */
    public function getTindakanList()
    {
        $tindakan = Tindakan::with('spesialis')->orderBy('nama')->get();
        return response()->json($tindakan);
    }

    public function searchTindakan(Request $request)
{
    $search = $request->input('q');
    $spesialisasiId = $request->input('spesialisasi_id');
    
    $query = \App\Models\ERM\Tindakan::with('spesialis')
        ->when($spesialisasiId, function($q) use ($spesialisasiId) {
            $q->where('spesialis_id', $spesialisasiId);
        })
        ->when($search, function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%");
        })
        ->orderBy('nama')
        ->limit(20)
        ->get();

    $results = [];
    foreach ($query as $tindakan) {
        $results[] = [
            'id' => $tindakan->id,
            'text' => $tindakan->nama, // Only display the tindakan name
            'harga' => $tindakan->harga, // <-- add harga to result
        ];
    }
    return response()->json(['results' => $results]);
}
}
