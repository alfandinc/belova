<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\Dokter;
use App\Models\ERM\HasilLis;
use App\Models\ERM\HasilEksternal;
use App\Models\ERM\LabHasil;
use App\Models\ERM\LabKategori;
use App\Models\ERM\LabPermintaan;
use App\Models\ERM\Pasien;
use App\Models\ERM\AsesmenPenunjang;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\LabTest;
use App\Models\ERM\MetodeBayar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ElabController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Add a subquery to calculate total nominal (sum of lab test prices) per visitation
            $subQuery = DB::table('erm_lab_permintaan')
                ->join('erm_lab_test', 'erm_lab_permintaan.lab_test_id', '=', 'erm_lab_test.id')
                ->selectRaw('erm_lab_permintaan.visitation_id, SUM(erm_lab_test.harga) as nominal')
                ->groupBy('erm_lab_permintaan.visitation_id');

            $visitations = Visitation::with(['pasien', 'metodeBayar'])
                ->select('erm_visitations.*')
                ->leftJoinSub($subQuery, 'lp', function($join) {
                    $join->on('lp.visitation_id', '=', 'erm_visitations.id');
                })
                ->addSelect(DB::raw('COALESCE(lp.nominal, 0) as nominal'));

            // Support date range filter
            if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
                $visitations->whereDate('tanggal_visitation', '>=', $request->tanggal_start)
                    ->whereDate('tanggal_visitation', '<=', $request->tanggal_end);
            } elseif ($request->filled('tanggal')) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }

            // Include jenis_kunjungan = 3 OR jenis_kunjungan = 1 but only when
            // there is at least one lab request (erm_lab_permintaan) for the visitation.
            $visitations->where(function($q) {
                $q->where('jenis_kunjungan', 3)
                  ->orWhere(function($q2) {
                      $q2->where('jenis_kunjungan', 1)
                         ->whereExists(function($sub) {
                             $sub->select(DB::raw(1))
                                 ->from('erm_lab_permintaan')
                                 ->whereColumn('erm_lab_permintaan.visitation_id', 'erm_visitations.id');
                         });
                  });
            });

            // Only include visitations with status_kunjungan 1 or 2 (open or in-progress)
            $visitations->whereIn('status_kunjungan', [1, 2]);

            $user = Auth::user();
            // Perawat should still see only status 2 (as before)
            if ($user->hasRole('Perawat')) {
                $visitations->where('status_kunjungan', 2);
            }

            // Calculate aggregated total nominal for the filtered visitations
            $totalNominalQuery = clone $visitations->getQuery();
            // Sum the nominal column (which comes from the leftJoinSub as lp.nominal via COALESCE)
            try {
                $totalNominal = $totalNominalQuery->get()->sum('nominal');
            } catch (\Exception $e) {
                // Fallback: 0 if anything goes wrong
                $totalNominal = 0;
                Log::error('Failed to calculate total nominal for elab index: ' . $e->getMessage());
            }

            return datatables()->of($visitations)
                ->with(['total_nominal' => $totalNominal])
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
        
        // Get all lab tests with their kategori for Select2 dropdown
        $labTests = LabTest::with('labKategori')->orderBy('nama')->get();
        
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

        // Get last assessment with diagnosa kerja for this patient
        $lastAsesmen = \App\Models\ERM\AsesmenPenunjang::whereHas('visitation', function($query) use ($visitation) {
                $query->where('pasien_id', $visitation->pasien_id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.elab.create', array_merge([
            'visitation' => $visitation,
            'labCategories' => $labCategories,
            'labTests' => $labTests,
            'totalHarga' => $totalHarga,
            'existingLabTestIds' => $existingLabTestIds,
            'existingLabTestStatuses' => $existingLabTestStatuses,
            'lastAsesmen' => $lastAsesmen
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

        // Check if a lab request already exists for this visitation and lab test
        $existingRequest = LabPermintaan::where('visitation_id', $request->visitation_id)
            ->where('lab_test_id', $request->lab_test_id)
            ->first();
            
        if ($existingRequest) {
            // If it exists, update its status to 'requested' (reactivate it)
            $existingRequest->status = 'requested';
            $existingRequest->save();
            
            // Make sure there's a billing entry
            if (!$existingRequest->billings()->exists()) {
                $labTest = LabTest::find($request->lab_test_id);
                $billing = new \App\Models\Finance\Billing([
                    'visitation_id' => $request->visitation_id,
                    'jumlah' => $labTest->harga,
                    'keterangan' => 'Lab: ' . $labTest->nama
                ]);
                
                $existingRequest->billings()->save($billing);
            }
            
            $labRequest = $existingRequest;
            $labRequest->load(['labTest.labKategori', 'dokter']);
        } else {
            // Create new lab request if it doesn't exist
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
        }

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
                // Use indoDate helper for Indonesian format
                return $this->indoDate($row->tanggal_visitation);
            })
            // New column: Pemeriksaan
            ->addColumn('pemeriksaan', function($row) {
                $hasilLis = \App\Models\ERM\HasilLis::where('visitation_id', $row->id)->pluck('nama_test')->toArray();
                return implode(', ', $hasilLis);
            })
            ->addColumn('dokter', function($row) {
                return $row->dokter && $row->dokter->user 
                    ? $row->dokter->user->name 
                    : 'Tidak ada dokter';
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-view-hasil-lis" data-id="'.$row->id.'" title="Lihat Hasil">
                            <i class="fas fa-eye"></i> Lihat
                        </button>
                        <button type="button" class="btn btn-sm btn-primary btn-add-hasil-lis ml-1" data-id="'.$row->id.'" title="Tambah Hasil">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <a href="'.route('erm.elab.hasil-lis.pdf', $row->id).'" class="btn btn-sm btn-danger ml-1" title="Cetak PDF Hasil Lab" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>';
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

    public function getPatientLabHistory($pasienId)
    {
        // First, try to find the patient
        $pasien = Pasien::find($pasienId);
        
        if (!$pasien) {
            return DataTables::of(collect([]))->make(true);
        }
        
        // Get all visitations for this patient
        $visitations = Visitation::where('pasien_id', $pasienId)
                                ->whereExists(function ($query) {
                                    $query->select(DB::raw(1))
                                          ->from('erm_lab_permintaan')
                                          ->whereColumn('erm_lab_permintaan.visitation_id', 'erm_visitations.id');
                                })
                                ->with(['dokter.user', 'pasien'])
                                ->orderBy('tanggal_visitation', 'desc');
        
        return DataTables::of($visitations)
            ->addIndexColumn()
            ->addColumn('tanggal', function($row) {
                return date('d-m-Y', strtotime($row->tanggal_visitation));
            })
            ->addColumn('dokter', function($row) {
                if ($row->dokter && $row->dokter->user) {
                    return $row->dokter->user->name;
                }
                return 'N/A';
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-view-lab-history" data-id="'.$row->id.'">Lihat</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getVisitationLabDetail($visitationId)
    {
        $labPermintaan = LabPermintaan::with(['labTest.labKategori', 'dokter.user'])
                            ->where('visitation_id', $visitationId)
                            ->orderBy('created_at', 'desc')
                            ->get();

        // Transform the data to include formatted display
        $data = $labPermintaan->map(function($item) {
            // Format status for display
            $statusLabel = '';
            if ($item->status == 'requested') {
                $statusLabel = '<span class="badge badge-warning text-dark">Diminta</span>';
            } elseif ($item->status == 'processing') {
                $statusLabel = '<span class="badge badge-info text-white">Diproses</span>';
            } elseif ($item->status == 'completed') {
                $statusLabel = '<span class="badge badge-success text-white">Selesai</span>';
            }

            // Return formatted data
            return [
                'id' => $item->id,
                'tanggal' => $item->created_at->format('d-m-Y H:i'),
                'nama_pemeriksaan' => $item->labTest->nama,
                'kategori' => $item->labTest->labKategori->nama,
                'harga' => 'Rp ' . number_format($item->labTest->harga, 0, ',', '.'),
                'status_label' => $statusLabel,
                'dokter' => $item->dokter->user->name ?? 'N/A'
            ];
        });

        return response()->json([
            'data' => $data
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            // allow requests to be nullable so client can send an empty payload to trigger deletions
            'requests' => 'nullable|array'
        ]);

        try {
            $visitationId = $request->visitation_id;
            // ensure we have an array to work with even if client didn't send any requests
            $requestsData = $request->requests ?? [];
            
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
                'message' => 'Permintaan lab berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan permintaan lab: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getHasilEksternalData($visitationId)
    {
        // Get patient ID from visitation
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;
        
        // Get all visitations for this patient to show all lab results
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id');
        
        // Query to get all external lab results for this patient
        $query = HasilEksternal::whereIn('visitation_id', $visitationIds)
                ->orderBy('tanggal_pemeriksaan', 'desc');
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('asal_lab', function($row) {
                return $row->asal_lab;
            })
            // New column: Nama Pemeriksaan
            ->addColumn('nama_pemeriksaan', function($row) {
                return $row->nama_pemeriksaan;
            })
            ->addColumn('dokter', function($row) {
                return $row->dokter;
            })
            ->addColumn('tanggal_pemeriksaan', function($row) {
                // Use indoDate helper for Indonesian format
                return $this->indoDate($row->tanggal_pemeriksaan);
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-view-hasil-eksternal" data-id="'.$row->id.'">
                            <i class="fas fa-eye"></i> Lihat Hasil
                        </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getHasilEksternalDetail($id)
    {
        $hasilEksternal = HasilEksternal::findOrFail($id);
        
        // Prepare file URL if exists
        $fileUrl = null;
        if ($hasilEksternal->file_path) {
            $fileUrl = asset('storage/' . $hasilEksternal->file_path);
        }
        
        return response()->json([
            'data' => $hasilEksternal,
            'fileUrl' => $fileUrl
        ]);
    }

    public function storeHasilEksternal(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'asal_lab' => 'required|string|max:255',
            'nama_pemeriksaan' => 'required|string|max:255',
            'tanggal_pemeriksaan' => 'required|date',
            'dokter' => 'required|string|max:255',
            'catatan' => 'nullable|string',
            'file_hasil' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);
        
        try {
            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file_hasil')) {
                $file = $request->file('file_hasil');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('hasil_lab_eksternal', $fileName, 'public');
            }
            
            // Create hasil eksternal record
            $hasilEksternal = HasilEksternal::create([
                'visitation_id' => $request->visitation_id,
                'asal_lab' => $request->asal_lab,
                'nama_pemeriksaan' => $request->nama_pemeriksaan,
                'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
                'dokter' => $request->dokter,
                'catatan' => $request->catatan,
                'file_path' => $filePath,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Hasil lab eksternal berhasil ditambahkan',
                'data' => $hasilEksternal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan hasil lab eksternal: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function storeHasilLis(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            // kode_lis is now auto-generated
            'header' => 'nullable|string|max:255',
            'sub_header' => 'nullable|string|max:255',
            'nama_test' => 'required|string|max:255',
            'hasil' => 'nullable|string|max:255',
            'flag' => 'nullable|string|max:255',
            'metode' => 'nullable|string|max:255',
            'nilai_rujukan' => 'nullable|string|max:255',
            'satuan' => 'nullable|string|max:255',
        ]);
        
        try {
            // Generate a unique code for primary key
            $kode = 'LIS-' . uniqid();
            
            // Generate kode_lis if not provided
            $kodeLis = $request->kode_lis ?: 'LISNO-' . date('YmdHis');
            
            $hasilLis = HasilLis::create([
                'kode' => $kode,
                'visitation_id' => $request->visitation_id,
                'kode_lis' => $kodeLis,
                'header' => $request->header,
                'sub_header' => $request->sub_header,
                'nama_test' => $request->nama_test,
                'hasil' => $request->hasil,
                'flag' => $request->flag,
                'metode' => $request->metode,
                'nilai_rujukan' => $request->nilai_rujukan,
                'satuan' => $request->satuan,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Hasil LIS berhasil ditambahkan',
                'data' => $hasilLis
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Error adding LIS result: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan hasil LIS: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    // Helper function for date formatting
    private function indoDate($date) 
    {
        if (!$date) return '-';
        
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $dt = strtotime($date);
        $tgl = date('j', $dt);
        $bln = $bulan[(int)date('n', $dt)];
        $thn = date('Y', $dt);
        return "$tgl $bln $thn";
    }

    // Helper function for age calculation
    private function umurString($tanggal_lahir) 
    {
        $birth = new \DateTime($tanggal_lahir);
        $today = new \DateTime();
        $diff = $today->diff($birth);
        $umur = $diff->y . ' th';
        if ($diff->m > 0) $umur .= ' ' . $diff->m . ' bl';
        if ($diff->d > 0) $umur .= ' ' . $diff->d . ' hr';
        return $umur;
    }
    
    public function generateHasilLisPdf($visitationId)
    {
        try {
            // Get the visitation data with eager loading to avoid N+1 queries
            $visitation = Visitation::with([
                'pasien', 
                'dokter.user', 
                'dokter.spesialisasi'
            ])->findOrFail($visitationId);

            // Get all HasilLis entries for this visitation
            $hasilLis = HasilLis::where('visitation_id', $visitationId)->get();

            // Group data by header and sub_header - This is more efficient as one pass
            $groupedData = [];
            foreach ($hasilLis as $item) {
                // Skip items with empty header
                if (!$item->header) continue;

                if (!isset($groupedData[$item->header])) {
                    $groupedData[$item->header] = [];
                }

                // Default to empty string if sub_header is null
                $subHeader = $item->sub_header ?: '';

                if (!isset($groupedData[$item->header][$subHeader])) {
                    $groupedData[$item->header][$subHeader] = [];
                }

                $groupedData[$item->header][$subHeader][] = $item;
            }

            // Pre-load and cache background image
            $backgroundPath = public_path('img/overlay-lab.png');
            $backgroundData = '';
            if (file_exists($backgroundPath)) {
                // Optimize the image before encoding
                $backgroundData = 'data:image/png;base64,' . base64_encode(file_get_contents($backgroundPath));
            }

            // Get diagnosa kerja 1-5 from the latest asesmen penunjang for this patient
            $latestPenunjang = \App\Models\ERM\AsesmenPenunjang::whereHas('visitation', function($query) use ($visitation) {
                $query->where('pasien_id', $visitation->pasien_id);
            })
            ->orderBy('created_at', 'desc')
            ->first();
            $diagnosaKerja = [];
            if ($latestPenunjang) {
                for ($i = 1; $i <= 5; $i++) {
                    $val = $latestPenunjang->{'diagnosakerja_' . $i} ?? null;
                    if ($val) $diagnosaKerja[] = $val;
                }
            }
            
            // Get lab doctor with specialization ID 7 (pre-fetch this data)
            $dokterLab = \App\Models\ERM\Dokter::with('user', 'spesialisasi')
                ->where('spesialisasi_id', 7)
                ->first();
                
            // Pre-process QR code image to improve performance
            $qrCodeData = null;
            if ($dokterLab && $dokterLab->ttd) {
                $qrPath = public_path('img/qr/' . $dokterLab->ttd);
                if (file_exists($qrPath)) {
                    // Convert to base64 to embed directly in the PDF
                    $qrCodeData = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
                }
            }

            // Format dates before passing to view
            $tanggalVisitation = $this->indoDate($visitation->tanggal_visitation);
            $tanggalLahir = $this->indoDate($visitation->pasien->tanggal_lahir);
            $umurPasien = $this->umurString($visitation->pasien->tanggal_lahir);
            $tanggalSekarang = $this->indoDate(date('Y-m-d'));
            
            // Render blade ke HTML
            $html = view('erm.elab.pdf.hasil-lis', [
                'visitation' => $visitation,
                'hasilLis' => $hasilLis,
                'groupedData' => $groupedData,
                'tanggal' => date('d-m-Y', strtotime($visitation->tanggal_visitation)),
                'backgroundData' => $backgroundData,
                'diagnosaKerja' => $diagnosaKerja,
                'dokterLab' => $dokterLab ? [
                    'name' => $dokterLab->user->name ?? '-',
                    'spesialisasi' => $dokterLab->spesialisasi->nama ?? ''
                ] : null,
                'qrCodeData' => $qrCodeData,
                'tanggalVisitation' => $tanggalVisitation,
                'tanggalLahir' => $tanggalLahir,
                'umurPasien' => $umurPasien,
                'tanggalSekarang' => $tanggalSekarang
            ])->render();
            // === mPDF ===
            $headerImg = public_path('img/lab_header.png');
            $footerImg = public_path('img/lab_footer.png');
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 40, // 40mm for header image
                'margin_bottom' => 40, // 40mm for footer image
                'margin_left' => 10, // margin konten
                'margin_right' => 10, // margin konten
            ]);
            // Set header image benar-benar mepet tepi kertas
            if (file_exists($headerImg)) {
                $headerHtml = '<div style="position:absolute;left:0;top:0;width:210mm;height:40mm;margin:0;padding:0;"><img src="' . $headerImg . '" style="width:210mm;height:40mm;object-fit:cover;display:block;margin:0;padding:0;"></div>';
                $mpdf->SetHTMLHeader($headerHtml, 'O', true);
                $mpdf->SetHTMLHeader($headerHtml, 'E', true);
            }
            // Set footer image benar-benar mepet tepi bawah, kanan, kiri (40mm height)
            if (file_exists($footerImg)) {
                $footerHtml = '<div style="position:absolute;left:0;bottom:0;width:210mm;height:40mm;margin:0;padding:0;"><img src="' . $footerImg . '" style="width:210mm;height:40mm;object-fit:cover;display:block;margin:0;padding:0;"></div>';
                $mpdf->SetHTMLFooter($footerHtml, 'O', true);
                $mpdf->SetHTMLFooter($footerHtml, 'E', true);
            }
            $mpdf->WriteHTML($html);
            $filename = 'Hasil_Lab_' . $visitation->pasien->no_rekam_medis . '_' . date('dmY') . '.pdf';
            return response($mpdf->Output($filename, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function generateLembarMonitoringPdf($pasienId)
    {
        try {
            // Get the patient data
            $pasien = \App\Models\ERM\Pasien::findOrFail($pasienId);
            
            // Get the latest visitation for this patient
            $latestVisit = Visitation::with(['dokter.user'])
                ->where('pasien_id', $pasienId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Find visitations that have HasilLis entries for this patient
            $visitationsWithLis = HasilLis::select('erm_hasil_lis.visitation_id')
                ->join('erm_visitations', 'erm_hasil_lis.visitation_id', '=', 'erm_visitations.id')
                ->where('erm_visitations.pasien_id', $pasienId)
                ->groupBy('erm_hasil_lis.visitation_id')
                ->orderBy('erm_visitations.tanggal_visitation', 'desc')
                ->limit(5)
                ->get()
                ->pluck('visitation_id')
                ->toArray();
            
            // Get those visitations
            $visitations = Visitation::whereIn('id', $visitationsWithLis)
                ->orderBy('tanggal_visitation', 'desc')
                ->get();
            
            // If no visitations with LIS data found, return an error
            if ($visitations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data laboratorium untuk pasien ini.'
                ], 404);
            }
            
            // Get all HasilLis entries for these visitations
            $visitationIds = $visitations->pluck('id')->toArray();
            $allHasilLis = HasilLis::whereIn('visitation_id', $visitationIds)
                ->orderBy('header')
                ->orderBy('sub_header')
                ->orderBy('nama_test')
                ->get();
            
            // Create an array of visitation dates in the format we want to display
            // Always ensure we have 5 columns for display
            $visitDates = [];
            foreach ($visitations as $visit) {
                $visitDates[] = $this->indoDate($visit->tanggal_visitation);
            }
            while (count($visitDates) < 5) {
                $visitDates[] = "";
            }
            // Group and organize data for the monitoring format
            $monitoringData = [];
            foreach ($allHasilLis as $item) {
                if (!$item->header || empty($item->hasil)) continue;
                if (!isset($monitoringData[$item->header])) {
                    $monitoringData[$item->header] = [];
                }
                $subHeader = $item->sub_header ?: '';
                if (!isset($monitoringData[$item->header][$subHeader])) {
                    $monitoringData[$item->header][$subHeader] = [];
                }
                $visitation = $visitations->firstWhere('id', $item->visitation_id);
                $visitationDate = $this->indoDate($visitation->tanggal_visitation);
                if (!isset($monitoringData[$item->header][$subHeader][$item->nama_test])) {
                    $monitoringData[$item->header][$subHeader][$item->nama_test] = [];
                }
                $monitoringData[$item->header][$subHeader][$item->nama_test][$visitationDate] = [
                    'hasil' => $item->hasil,
                    'flag' => $item->flag
                ];
            }
            // Background image for blade (use header image for background)
            $headerImg = public_path('img/lab_header.png');
            $footerImg = public_path('img/lab_footer.png');
            $backgroundData = file_exists($headerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerImg)) : '';
            // Diagnosa kerja dari asesmen penunjang terbaru
            $latestPenunjang = \App\Models\ERM\AsesmenPenunjang::whereHas('visitation', function($query) use ($pasienId) {
                $query->where('pasien_id', $pasienId);
            })
            ->orderBy('created_at', 'desc')
            ->first();
            $diagnosaKerja = [];
            if ($latestPenunjang) {
                for ($i = 1; $i <= 5; $i++) {
                    $val = $latestPenunjang->{'diagnosakerja_' . $i} ?? null;
                    if ($val) $diagnosaKerja[] = $val;
                }
            }
            // Dokter lab (spesialisasi_id 7)
            $dokterLab = \App\Models\ERM\Dokter::with('user', 'spesialisasi')
                ->where('spesialisasi_id', 7)
                ->first();
            $qrCodeData = null;
            if ($dokterLab && $dokterLab->ttd) {
                $qrPath = public_path('img/qr/' . $dokterLab->ttd);
                if (file_exists($qrPath)) {
                    $qrCodeData = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
                }
            }
            $tanggalLahir = $this->indoDate($pasien->tanggal_lahir);
            $umurPasien = $this->umurString($pasien->tanggal_lahir);
            $tanggalSekarang = $this->indoDate(date('Y-m-d'));
            // Render blade ke HTML
            $html = view('erm.elab.pdf.lembar-monitoring', [
                'pasien' => $pasien,
                'latestVisit' => $latestVisit,
                'monitoringData' => $monitoringData,
                'visitDates' => $visitDates,
                'backgroundData' => $backgroundData,
                'diagnosaKerja' => $diagnosaKerja,
                'dokterLab' => $dokterLab ? [
                    'name' => $dokterLab->user->name ?? '-',
                    'spesialisasi' => $dokterLab->spesialisasi->nama ?? ''
                ] : null,
                'qrCodeData' => $qrCodeData,
                'tanggalLahir' => $tanggalLahir,
                'umurPasien' => $umurPasien,
                'tanggalSekarang' => $tanggalSekarang
            ])->render();
            // === mPDF ===
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 40, // 40mm for header image
                'margin_bottom' => 20, // 20mm for footer image
                'margin_left' => 10, // margin konten
                'margin_right' => 10, // margin konten
            ]);
            // Set header image benar-benar mepet tepi kertas
            if (file_exists($headerImg)) {
                $headerHtml = '<div style="position:absolute;left:0;top:0;width:210mm;height:40mm;margin:0;padding:0;"><img src="' . $headerImg . '" style="width:210mm;height:40mm;object-fit:cover;display:block;margin:0;padding:0;"></div>';
                $mpdf->SetHTMLHeader($headerHtml, 'O', true);
                $mpdf->SetHTMLHeader($headerHtml, 'E', true);
            }
            // Set footer image benar-benar mepet tepi bawah, kanan, kiri (40mm height)
            if (file_exists($footerImg)) {
                $footerHtml = '<div style="position:absolute;left:0;bottom:0;width:210mm;height:40mm;margin:0;padding:0;"><img src="' . $footerImg . '" style="width:210mm;height:40mm;object-fit:cover;display:block;margin:0;padding:0;"></div>';
                $mpdf->SetHTMLFooter($footerHtml, 'O', true);
                $mpdf->SetHTMLFooter($footerHtml, 'E', true);
            }
            $mpdf->WriteHTML($html);
            $filename = 'Monitoring_Lab_' . $pasien->id . '_' . date('dmY') . '.pdf';
            return response($mpdf->Output($filename, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}