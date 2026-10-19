<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\RadiologiHasil;
use App\Models\ERM\RadiologiKategori;
use App\Models\ERM\RadiologiPermintaan;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\RadiologiTest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EradiologiController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        // Picker: kategori with their tests (small master, rendered on the page like E-Lab)
        $radiologiCategories = RadiologiKategori::with(['radiologiTests' => fn ($q) => $q->orderBy('nama')])
            ->orderBy('nama')->get();

        // radiologi_test_id => status of what is already saved for this visit
        $existingStatuses = RadiologiPermintaan::where('visitation_id', $visitationId)
            ->pluck('status', 'radiologi_test_id')->toArray();

        $pasienVisitIds = Visitation::select('id')->where('pasien_id', $visitation->pasien_id);
        // Last earlier visit with radiologi requests, for "ulangi permintaan sebelumnya"
        $previousVisit = Visitation::where('pasien_id', $visitation->pasien_id)
            ->where('id', '!=', $visitation->id)
            ->whereExists(fn ($q) => $q->select(\DB::raw(1))->from('erm_radiologi_permintaan')
                ->whereColumn('erm_radiologi_permintaan.visitation_id', 'erm_visitations.id'))
            ->orderByDesc('tanggal_visitation')
            ->first(['id', 'tanggal_visitation']);
        $previousRadiologi = $previousVisit ? [
            'tanggal' => \Carbon\Carbon::parse($previousVisit->tanggal_visitation)->format('d-m-Y'),
            'test_ids' => RadiologiPermintaan::where('visitation_id', $previousVisit->id)->pluck('radiologi_test_id')->values(),
        ] : null;

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.eradiologi.create', array_merge([
            'visitation' => $visitation,
            'radiologiCategories' => $radiologiCategories,
            'existingStatuses' => $existingStatuses,
            'previousRadiologi' => $previousRadiologi,
            'hasilCount' => RadiologiHasil::whereIn('visitation_id', $pasienVisitIds)->count(),
            'hasilThisVisit' => RadiologiHasil::where('visitation_id', $visitation->id)->count(),
            'riwayatCount' => RadiologiPermintaan::whereIn('visitation_id', $pasienVisitIds)->distinct()->count('visitation_id'),
            'canEditStatus' => Auth::user() && Auth::user()->hasAnyRole(['Lab', 'Admin']),
            // Diagnosa kerja shown above the picker (same source as E-Lab)
            'lastAsesmen' => \App\Models\ERM\AsesmenPenunjang::whereHas('visitation', fn ($q) => $q->where('pasien_id', $visitation->pasien_id))
                ->latest()->first(),
        ], $pasienData, $createKunjunganData));
    }

    /**
     * Saves the whole permintaan list of a visit at once (same flow as E-Lab): new tests are created with their
     * billing, unchecked ones removed with their billing, statuses updated. Tests the lab already works on
     * (Diproses / Selesai) are never removed here, and only Lab / Admin change statuses.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'requests' => 'nullable|array',
            'requests.*' => 'in:requested,processing,completed',
        ]);

        $visitationId = $request->visitation_id;
        $wanted = collect($request->input('requests', []))->mapWithKeys(fn ($status, $testId) => [(int) $testId => $status]);
        $canEditStatus = Auth::user() && Auth::user()->hasAnyRole(['Lab', 'Admin']);

        \DB::transaction(function () use ($visitationId, $wanted, $canEditStatus) {
            $existing = RadiologiPermintaan::where('visitation_id', $visitationId)->get()->keyBy('radiologi_test_id');

            foreach ($existing as $testId => $permintaan) {
                if ($wanted->has($testId)) {
                    $status = $wanted[$testId];
                    if ($canEditStatus && $permintaan->status !== $status) {
                        $permintaan->update(['status' => $status]);
                    }
                } elseif ($permintaan->status === 'requested') {
                    $permintaan->billings()->delete();
                    $permintaan->delete();
                }
            }

            $tests = RadiologiTest::whereIn('id', $wanted->keys()->diff($existing->keys()))->get();
            foreach ($tests as $test) {
                $permintaan = RadiologiPermintaan::create([
                    'visitation_id' => $visitationId,
                    'radiologi_test_id' => $test->id,
                    'status' => $canEditStatus ? $wanted[$test->id] : 'requested',
                    'dokter_id' => Auth::id(),
                ]);
                $permintaan->billings()->save(new \App\Models\Finance\Billing([
                    'visitation_id' => $visitationId,
                    'jumlah' => $test->harga,
                    'keterangan' => 'Radiologi: ' . $test->nama,
                ]));
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Permintaan radiologi berhasil disimpan',
            'statuses' => RadiologiPermintaan::where('visitation_id', $visitationId)->pluck('status', 'radiologi_test_id'),
        ]);
    }

    /** Visits of the patient with radiologi requests (Riwayat modal). */
    public function patientHistory($pasienId)
    {
        $query = Visitation::where('pasien_id', $pasienId)
            ->whereExists(fn ($q) => $q->select(\DB::raw(1))->from('erm_radiologi_permintaan')
                ->whereColumn('erm_radiologi_permintaan.visitation_id', 'erm_visitations.id'))
            ->with(['dokter.user'])
            ->orderBy('tanggal_visitation', 'desc');

        return DataTables::of($query)
            ->addColumn('tanggal', fn ($row) => date('d-m-Y', strtotime($row->tanggal_visitation)))
            ->addColumn('dokter', fn ($row) => $row->dokter && $row->dokter->user ? $row->dokter->user->name : 'N/A')
            ->make(true);
    }

    /** Requested tests of one visit (a Riwayat row opened). */
    public function visitationDetail($visitationId)
    {
        $labels = ['requested' => 'Diminta', 'processing' => 'Diproses', 'completed' => 'Selesai'];
        $data = RadiologiPermintaan::with('radiologiTest.radiologiKategori')
            ->where('visitation_id', $visitationId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($item) => [
                'nama_pemeriksaan' => optional($item->radiologiTest)->nama,
                'kategori' => optional(optional($item->radiologiTest)->radiologiKategori)->nama,
                'harga' => 'Rp ' . number_format((float) optional($item->radiologiTest)->harga, 0, ',', '.'),
                'status' => $item->status,
                'status_text' => $labels[$item->status] ?? $item->status,
            ]);

        return response()->json(['data' => $data]);
    }

    public function getRadiologiTestData(Request $request)
    {
        $query = RadiologiTest::with('radiologiKategori')
            ->orderBy('nama');

        if ($request->has('kategori_id') && $request->kategori_id) {
            $query->where('radiologi_kategori_id', $request->kategori_id);
        }
        
        return DataTables::of($query)
            ->addColumn('kategori', function($row) {
                return $row->radiologiKategori->nama;
            })
            ->addColumn('harga_formatted', function($row) {
                return 'Rp ' . number_format($row->harga, 0, ',', '.');
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-sm btn-primary btn-permintaan-radiologi" 
                            data-id="'.$row->id.'" 
                            data-nama="'.$row->nama.'"
                            data-kategori="'.$row->radiologiKategori->nama.'"
                            data-harga="'.$row->harga.'">
                            Buat
                        </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getRadiologiPermintaanData($visitationId)
    {
        $query = RadiologiPermintaan::with(['radiologiTest.radiologiKategori', 'dokter'])
                    ->where('visitation_id', $visitationId)
                    ->orderBy('created_at', 'desc');
        
        return DataTables::of($query)
            ->addColumn('checkbox', function($row){
                return '<input type="checkbox" class="permintaan-checkbox" value="'.$row->id.'">';
            })
            ->addColumn('tanggal', function($row) {
                return $row->created_at->format('d-m-Y H:i');
            })
            ->addColumn('nama_pemeriksaan', function($row) {
                return $row->radiologiTest->nama;
            })
            ->addColumn('kategori', function($row) {
                return $row->radiologiTest->radiologiKategori->nama;
            })
            ->addColumn('harga', function($row) {
                return 'Rp ' . number_format($row->radiologiTest->harga, 0, ',', '.');
            })
            ->addColumn('status_label', function($row) {
                if ($row->status == 'requested') {
                    return '<span class="badge badge-warning">Diminta</span>';
                } elseif ($row->status == 'processing') {
                    return '<span class="badge badge-info">Diproses</span>';
                } else {
                    return '<span class="badge badge-success">Selesai</span>';
                }
            })
            ->addColumn('action', function($row) {
                $editBtn = '<button class="btn btn-sm btn-info btn-edit-status mr-1" data-id="'.$row->id.'">
                                <i class="fas fa-edit"></i> Edit
                            </button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger btn-delete-permintaan" data-id="'.$row->id.'">
                                <i class="fas fa-trash"></i> Batal
                            </button>';
                return $editBtn . $deleteBtn;
            })
            ->rawColumns(['checkbox', 'status_label', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'radiologi_test_id' => 'required|exists:erm_radiologi_test,id'
        ]);

        // Create radiologi request
        $radiologiRequest = RadiologiPermintaan::create([
            'visitation_id' => $request->visitation_id,
            'radiologi_test_id' => $request->radiologi_test_id,
            'status' => 'requested',
            'dokter_id' => Auth::id()
        ]);

        $radiologiRequest->load(['radiologiTest.radiologiKategori', 'dokter']);

        // Create billing entry
        $radiologiTest = RadiologiTest::find($request->radiologi_test_id);
        $billing = new \App\Models\Finance\Billing([
            'visitation_id' => $request->visitation_id,
            'jumlah' => $radiologiTest->harga,
            'keterangan' => 'Radiologi: ' . $radiologiTest->nama
        ]);

        $radiologiRequest->billings()->save($billing);

        // Get updated total price
        $totalHarga = RadiologiPermintaan::where('visitation_id', $request->visitation_id)
                        ->with('radiologiTest')
                        ->get()
                        ->sum(function($item) {
                            return $item->radiologiTest->harga;
                        });

        return response()->json([
            'success' => true,
            'message' => 'Permintaan radiologi berhasil dibuat',
            'data' => $radiologiRequest,
            'totalHarga' => $totalHarga,
            'totalHargaFormatted' => 'Rp ' . number_format($totalHarga, 0, ',', '.')
        ]);
    }
    
    public function destroy($id)
    {
        try {
            $permintaan = RadiologiPermintaan::findOrFail($id);
            $visitation_id = $permintaan->visitation_id;
            
            // Delete related billing entries
            $permintaan->billings()->delete();

            // Delete the radiologi request
            $permintaan->delete();
            
            // Get updated total price
            $totalHarga = RadiologiPermintaan::where('visitation_id', $visitation_id)
                            ->with('radiologiTest')
                            ->get()
                            ->sum(function($item) {
                                return $item->radiologiTest->harga;
                            });
            
            return response()->json([
                'success' => true,
                'message' => 'Permintaan radiologi berhasil dibatalkan',
                'totalHarga' => $totalHarga,
                'totalHargaFormatted' => 'Rp ' . number_format($totalHarga, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan permintaan radiologi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:requested,processing,completed',
            'hasil' => 'nullable|string'
        ]);
        
        try {
            $permintaan = RadiologiPermintaan::findOrFail($id);
            $permintaan->status = $request->status;
            
            if ($request->has('hasil')) {
                $permintaan->hasil = $request->hasil;
            }
            
            $permintaan->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status permintaan radiologi berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status permintaan radiologi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:erm_radiologi_permintaan,id'
        ]);
        
        try {
            $visitation_id = RadiologiPermintaan::whereIn('id', $request->ids)->first()->visitation_id;
            
            // Get all radiologi permintaan records
            $permintaans = RadiologiPermintaan::whereIn('id', $request->ids)->get();
            
            // Delete related billing entries and radiologi permintaan
            foreach ($permintaans as $permintaan) {
                $permintaan->billings()->delete();
                $permintaan->delete();
            }
            
            // Get updated total price
            $totalHarga = RadiologiPermintaan::where('visitation_id', $visitation_id)
                            ->with('radiologiTest')
                            ->get()
                            ->sum(function($item) {
                                return $item->radiologiTest->harga;
                            });
            
            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' permintaan radiologi berhasil dibatalkan',
                'totalHarga' => $totalHarga,
                'totalHargaFormatted' => 'Rp ' . number_format($totalHarga, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan permintaan radiologi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:erm_radiologi_permintaan,id',
            'status' => 'required|in:requested,processing,completed'
        ]);
        
        try {
            RadiologiPermintaan::whereIn('id', $request->ids)->update([
                'status' => $request->status
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' permintaan radiologi berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status permintaan radiologi: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function printPermintaan($visitationId)
    {
        $visitation = Visitation::with(['pasien', 'dokter'])->findOrFail($visitationId);
        
        // Get all radiologi categories with their tests
        $radiologiCategories = RadiologiKategori::with(['radiologiTests' => function($query) {
            $query->orderBy('nama');
        }])->orderBy('nama')->get();
        
        // Get the radiologi requests for this visitation
        $radiologiRequests = RadiologiPermintaan::where('visitation_id', $visitationId)
                        ->with('radiologiTest')
                        ->get()
                        ->pluck('radiologi_test_id')
                        ->toArray();
        
        // Get patient data
        $pasienData = PasienHelperController::getDataPasien($visitationId);

        $pdf = Pdf::loadView('erm.eradiologi.print', [
            'visitation' => $visitation,
            'radiologiCategories' => $radiologiCategories,
            'radiologiRequests' => $radiologiRequests,
            'pasienData' => $pasienData
        ]);
        
        // Set paper size to match the form in the image
        $pdf->setPaper('a4');

        return $pdf->stream('Permintaan_Radiologi_' . $visitation->pasien->no_rm . '.pdf');
    }

    public function getRadiologiDokumenData($visitationId)
{
    // Get patient ID from visitation
    $visitation = Visitation::findOrFail($visitationId);
    $pasienId = $visitation->pasien_id;
    
    // Get all visitations for this patient to show all radiologi results
    $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id')->toArray();
    
    $query = RadiologiHasil::whereIn('visitation_id', $visitationIds)
                ->orderBy('created_at', 'desc');
    $canDelete = Auth::user() && Auth::user()->hasRole('Admin');

    return DataTables::of($query)
        ->addIndexColumn() // Adds row numbers
        ->addColumn('tanggal', function($row) {
            return $row->tanggal_pemeriksaan->format('d-m-Y');
        })
        ->addColumn('action', function($row) use ($canDelete) {
            $viewBtn = '<button class="btn btn-sm btn-info btn-view-radiologi" data-id="'.$row->id.'">
                            <i class="fas fa-eye"></i> Lihat
                        </button>';
            // Only Admin removes an uploaded result
            $deleteBtn = $canDelete
                ? '<button class="btn btn-sm btn-danger btn-delete-radiologi-hasil ml-1" data-id="'.$row->id.'" data-nama="'.e($row->nama_pemeriksaan).'">
                        <i class="fas fa-trash"></i> Hapus
                    </button>'
                : '';
            return $viewBtn . $deleteBtn;
        })
        ->rawColumns(['action'])
        ->make(true);
}

public function uploadRadiologiHasil(Request $request)
{
    $request->validate([
        'visitation_id' => 'required|exists:erm_visitations,id',
        'dokter_pengirim' => 'required|string|max:255',
        'nama_pemeriksaan' => 'required|string|max:255',
        'tanggal_pemeriksaan' => 'required|date',
        'deskripsi' => 'nullable|string',
        'hasil_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480', // 20MB max
    ]);

    // Handle file upload
    $filePath = null;
    if ($request->hasFile('hasil_file')) {
        $file = $request->file('hasil_file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        // Store in public disk
        $filePath = $file->storeAs('radiologi_hasil', $fileName, 'public');
    }

    // Create radiologi hasil record
    $radiologiHasil = RadiologiHasil::create([
        'visitation_id' => $request->visitation_id,
        'dokter_pengirim' => $request->dokter_pengirim,
        'nama_pemeriksaan' => $request->nama_pemeriksaan,
        'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
        'deskripsi' => $request->deskripsi,
        'file_path' => $filePath,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Hasil radiologi berhasil diupload',
        'data' => $radiologiHasil
    ]);
}

/** Removes an uploaded result and its file. Admin only. */
public function destroyRadiologiHasil($id)
{
    abort_unless(Auth::user() && Auth::user()->hasRole('Admin'), 403, 'Hanya Admin yang dapat menghapus hasil radiologi.');

    $radiologiHasil = RadiologiHasil::findOrFail($id);
    $filePath = $radiologiHasil->file_path;
    $radiologiHasil->delete();
    if ($filePath && \Storage::disk('public')->exists($filePath)) {
        \Storage::disk('public')->delete($filePath);
    }

    return response()->json([
        'success' => true,
        'message' => 'Hasil radiologi berhasil dihapus.',
    ]);
}

public function getRadiologiHasilDetails($id)
{
    $radiologiHasil = RadiologiHasil::findOrFail($id);
    
    return response()->json([
        'success' => true,
        'data' => $radiologiHasil
    ]);
}

public function updateRadiologiHasilDescription(Request $request, $id)
{
    $validated = $request->validate([
        'deskripsi' => 'nullable|string',
    ]);

    $radiologiHasil = RadiologiHasil::findOrFail($id);
    $radiologiHasil->deskripsi = $validated['deskripsi'] ?? null;
    $radiologiHasil->save();

    return response()->json([
        'success' => true,
        'message' => 'Deskripsi hasil radiologi berhasil diperbarui.',
        'data' => $radiologiHasil,
    ]);
}
}