<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\Dokter;
use App\Models\ERM\HasilLis;
use App\Models\ERM\LabHasil;
use App\Models\ERM\LabKategori;
use App\Models\ERM\LabPermintaan;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\LabTest;
use App\Models\ERM\MetodeBayar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ElabController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            $visitations->whereIn('jenis_kunjungan', [3]);

            $user = Auth::user();
            if ($user->hasRole('Perawat')) {
                $visitations->where('status_kunjungan', 2);
            }

            return datatables()->of($visitations)
                ->addColumn('antrian', fn($v) => $v->no_antrian) // ✅ antrian dari database
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-')
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal_visitation', fn($v) => $v->tanggal_visitation)
                ->addColumn('status_dokumen', fn($v) => ucfirst($v->status_dokumen))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('status_kunjungan', fn($v) => $v->progress) // 🛠️ Tambah kolom progress!
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $asesmenUrl = $user->hasRole('Perawat') || $user->hasRole('Lab') ? route('erm.elab.create', $v->id)
                        : ($user->hasRole('Dokter') ? route('erm.elab.create', $v->id) : '#');
                    return '<a href="' . $asesmenUrl . '" class="btn btn-sm btn-primary">Lihat</a> ';
                })
                ->rawColumns(['dokumen'])
                ->make(true);
        }

        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        return view('erm.elab.index', compact('dokters', 'metodeBayar'));
    }
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        
        // Get lab categories with their tests
        $labCategories = LabKategori::with(['labTests' => function($query) {
            $query->orderBy('nama');
        }])->orderBy('nama')->get();
        
        // Get existing lab requests for this visitation
        $existingLabRequests = LabPermintaan::where('visitation_id', $visitationId)
                                ->with('labTest')
                                ->get();
        
        // Get total estimated price
        $totalHarga = $existingLabRequests->sum(function($item) {
            return $item->labTest->harga;
        });
        
        // Get existing lab test IDs for pre-checking checkboxes
        $existingLabTestIds = $existingLabRequests->pluck('lab_test_id')->toArray();
        // Map: lab_test_id => status
        $existingLabTestStatuses = $existingLabRequests->pluck('status', 'lab_test_id')->toArray();

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.elab.create', array_merge([
            'visitation' => $visitation,
            'labCategories' => $labCategories,
            'totalHarga' => $totalHarga,
            'existingLabTestIds' => $existingLabTestIds,
            'existingLabTestStatuses' => $existingLabTestStatuses
        ], $pasienData, $createKunjunganData));
    }

    public function getLabPermintaanData($visitationId)
    {
        $query = LabPermintaan::with(['labTest.labKategori', 'dokter'])
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
                return $row->labTest->nama;
            })
            ->addColumn('kategori', function($row) {
                return $row->labTest->labKategori->nama;
            })
            ->addColumn('harga', function($row) {
                return 'Rp ' . number_format($row->labTest->harga, 0, ',', '.');
            })
            ->addColumn('lab_test_id', function($row) {
                return $row->lab_test_id;
            })
            ->addColumn('status_label', function($row) {
                if ($row->status == 'requested') {
                    return '<span class="badge badge-warning text-dark">Diminta</span>';
                } elseif ($row->status == 'processing') {
                    return '<span class="badge badge-info text-white">Diproses</span>';
                } else {
                    return '<span class="badge badge-success text-white">Selesai</span>';
                }
            })
            ->addColumn('action', function($row) {
                $editBtn = '<button class="btn btn-sm btn-info btn-edit-status mr-1" data-id="'.$row->id.'">
                                <i class="fas fa-edit"></i> Edit
                            </button>';
                $deleteBtn = '<button class="btn btn-sm btn-danger btn-delete-permintaan" data-id="'.$row->id.'" data-test-id="'.$row->lab_test_id.'">
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
            'lab_test_id' => 'required|exists:erm_lab_test,id'
        ]);

        // Create lab request
        $labRequest = LabPermintaan::create([
            'visitation_id' => $request->visitation_id,
            'lab_test_id' => $request->lab_test_id,
            'status' => 'requested',
            'dokter_id' => Auth::id()
        ]);

        $labRequest->load(['labTest.labKategori', 'dokter']);
        
        // Create billing entry
        $labTest = LabTest::find($request->lab_test_id);
        $billing = new \App\Models\Finance\Billing([
            'visitation_id' => $request->visitation_id,
            'jumlah' => $labTest->harga,
            'keterangan' => 'Lab: ' . $labTest->nama
        ]);
        
        $labRequest->billings()->save($billing);

        // Get updated total price
        $totalHarga = LabPermintaan::where('visitation_id', $request->visitation_id)
                        ->with('labTest')
                        ->get()
                        ->sum(function($item) {
                            return $item->labTest->harga;
                        });

        return response()->json([
            'success' => true,
            'message' => 'Permintaan lab berhasil dibuat',
            'data' => $labRequest,
            'totalHarga' => $totalHarga,
            'totalHargaFormatted' => 'Rp ' . number_format($totalHarga, 0, ',', '.')
        ]);
    }
    
    public function destroy($id)
    {
        try {
            $permintaan = LabPermintaan::findOrFail($id);
            $visitation_id = $permintaan->visitation_id;
            
            // Delete related billing entries
            $permintaan->billings()->delete();
            
            // Delete the lab request
            $permintaan->delete();
            
            // Get updated total price
            $totalHarga = LabPermintaan::where('visitation_id', $visitation_id)
                            ->with('labTest')
                            ->get()
                            ->sum(function($item) {
                                return $item->labTest->harga;
                            });
            
            return response()->json([
                'success' => true,
                'message' => 'Permintaan lab berhasil dibatalkan',
                'totalHarga' => $totalHarga,
                'totalHargaFormatted' => 'Rp ' . number_format($totalHarga, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan permintaan lab: ' . $e->getMessage()
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
            $permintaan = LabPermintaan::findOrFail($id);
            $permintaan->status = $request->status;
            
            if ($request->has('hasil')) {
                $permintaan->hasil = $request->hasil;
            }
            
            $permintaan->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status permintaan lab berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status permintaan lab: ' . $e->getMessage()
            ], 500);
        }
    }
        
    public function getHasilLisData($visitationId)
    {
        // Get patient ID from visitation
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;
        
        // Get all visitations for this patient to show all lab results
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id');
        
        // Query to get unique visitation IDs that have LIS results
        // We'll select directly from visitations to ensure one row per visitation
        $query = Visitation::whereIn('id', $visitationIds)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('erm_hasil_lis')
                          ->whereRaw('erm_hasil_lis.visitation_id = erm_visitations.id');
                })
                ->with(['dokter.user'])
                ->orderBy('tanggal_visitation', 'desc');
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', function($row) {
                return date('d-m-Y', strtotime($row->tanggal_visitation));
            })
            ->addColumn('dokter', function($row) {
                return $row->dokter && $row->dokter->user 
                    ? $row->dokter->user->name 
                    : 'Tidak ada dokter';
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-view-hasil-lis" data-id="'.$row->id.'">
                            <i class="fas fa-eye"></i> Lihat Hasil
                        </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getHasilLisDetails($visitationId)
    {
        $hasilLis = HasilLis::where('visitation_id', $visitationId)->get();
        
        return response()->json([
            'data' => $hasilLis
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'requests' => 'required|array'
        ]);

        try {
            $visitationId = $request->visitation_id;
            $requestsData = $request->requests;
            
            // Get all lab tests that are checked (included in the request)
            $labTestIds = [];
            foreach ($requestsData as $key => $requestData) {
                if (isset($key) && is_numeric($key)) {
                    $labTestIds[] = $key;
                }
            }
            
            // First, handle deletion of any lab tests that were unchecked
            // Get all existing lab requests for this visitation
            $existingRequests = LabPermintaan::where('visitation_id', $visitationId)->get();
            
            // Delete any existing requests that are not in the new request data
            foreach ($existingRequests as $existingRequest) {
                if (!in_array($existingRequest->lab_test_id, $labTestIds)) {
                    // Delete related billing entries
                    $existingRequest->billings()->delete();
                    
                    // Delete the request
                    $existingRequest->delete();
                }
            }
            
            // Process each lab test in the request
            foreach ($requestsData as $labTestId => $requestData) {
                if (is_numeric($labTestId)) {
                    // Check if request already exists
                    $existingRequest = LabPermintaan::where('visitation_id', $visitationId)
                                        ->where('lab_test_id', $labTestId)
                                        ->first();
                    
                    $status = $requestData['status'] ?? 'requested';
                    
                    if ($existingRequest) {
                        // Update existing request
                        $existingRequest->status = $status;
                        $existingRequest->save();
                    } else {
                        // Create new request
                        $labRequest = new LabPermintaan([
                            'visitation_id' => $visitationId,
                            'lab_test_id' => $labTestId,
                            'status' => $status,
                            'dokter_id' => Auth::id()
                        ]);
                        $labRequest->save();
                        
                        // Create billing entry
                        $labTest = LabTest::find($labTestId);
                        if ($labTest) {
                            $billing = new \App\Models\Finance\Billing([
                                'visitation_id' => $visitationId,
                                'jumlah' => $labTest->harga,
                                'keterangan' => 'Lab: ' . $labTest->nama
                            ]);
                            $labRequest->billings()->save($billing);
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Permintaan lab berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan permintaan lab: ' . $e->getMessage()
            ], 500);
        }
    }
}