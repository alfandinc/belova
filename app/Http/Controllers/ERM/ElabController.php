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

    public function getLabTestData(Request $request)
    {
        $query = LabTest::with('labKategori')
            ->orderBy('nama');

        if ($request->has('kategori_id') && $request->kategori_id) {
            $query->where('lab_kategori_id', $request->kategori_id);
        }
        
        return DataTables::of($query)
            ->addColumn('kategori', function($row) {
                return $row->labKategori->nama;
            })
            ->addColumn('harga_formatted', function($row) {
                return 'Rp ' . number_format($row->harga, 0, ',', '.');
            })
            ->addColumn('action', function($row) {
                return '<button class="btn btn-sm btn-primary btn-permintaan-lab" 
                            data-id="'.$row->id.'" 
                            data-nama="'.$row->nama.'"
                            data-kategori="'.$row->labKategori->nama.'"
                            data-harga="'.$row->harga.'">
                            Buat
                        </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
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
    
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:erm_lab_permintaan,id'
        ]);
        
        try {
            $visitation_id = LabPermintaan::whereIn('id', $request->ids)->first()->visitation_id;
            
            // Get all lab permintaan records
            $permintaans = LabPermintaan::whereIn('id', $request->ids)->get();
            
            // Delete related billing entries and lab permintaan
            foreach ($permintaans as $permintaan) {
                $permintaan->billings()->delete();
                $permintaan->delete();
            }
            
            // Get updated total price
            $totalHarga = LabPermintaan::where('visitation_id', $visitation_id)
                            ->with('labTest')
                            ->get()
                            ->sum(function($item) {
                                return $item->labTest->harga;
                            });
            
            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' permintaan lab berhasil dibatalkan',
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
    
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:erm_lab_permintaan,id',
            'status' => 'required|in:requested,processing,completed'
        ]);
        
        try {
            LabPermintaan::whereIn('id', $request->ids)->update([
                'status' => $request->status
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' permintaan lab berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status permintaan lab: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function printPermintaan($visitationId)
    {
        $visitation = Visitation::with(['pasien', 'dokter'])->findOrFail($visitationId);
        
        // Get all lab categories with their tests
        $labCategories = LabKategori::with(['labTests' => function($query) {
            $query->orderBy('nama');
        }])->orderBy('nama')->get();
        
        // Get the lab requests for this visitation
        $labRequests = LabPermintaan::where('visitation_id', $visitationId)
                        ->with('labTest')
                        ->get()
                        ->pluck('lab_test_id')
                        ->toArray();
        
        // Get patient data
        $pasienData = PasienHelperController::getDataPasien($visitationId);
        
        $pdf = Pdf::loadView('erm.elab.print', [
            'visitation' => $visitation,
            'labCategories' => $labCategories,
            'labRequests' => $labRequests,
            'pasienData' => $pasienData
        ]);
        
        // Set paper size to match the form in the image
        $pdf->setPaper('a4');
        
        return $pdf->stream('Permintaan_Lab_' . $visitation->pasien->no_rm . '.pdf');
    }

    public function getLabHasilData($visitationId)
    {
        // Get patient ID from visitation
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;
        
        // Get all visitations for this patient to show all lab results
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id')->toArray();
        
        $query = LabHasil::whereIn('visitation_id', $visitationIds)
                    ->orderBy('created_at', 'desc');
        
        return DataTables::of($query)
            ->addIndexColumn() // Adds row numbers
            ->addColumn('tanggal', function($row) {
                return $row->tanggal_pemeriksaan->format('d-m-Y');
            })
            ->addColumn('asal_lab', function($row) {
                return $row->asal_lab === 'internal' ? 'Lab Internal' : 'Lab Eksternal';
            })
            ->addColumn('action', function($row) {
                $viewBtn = '<button class="btn btn-sm btn-info btn-view-hasil" data-id="'.$row->id.'">
                                <i class="fas fa-eye"></i> Lihat
                            </button>';
                return $viewBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function uploadLabHasil(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'asal_lab' => 'required|in:internal,eksternal',
            'nama_pemeriksaan' => 'required|string|max:255',
            'tanggal_pemeriksaan' => 'required|date',
            'dokter' => 'required|string|max:255',
            'catatan' => 'nullable|string',
            'hasil_file' => 'required_if:asal_lab,eksternal|file|mimes:pdf|max:10240', // 10MB max
            'hasil_detail' => 'nullable|array'
        ]);

        // Handle file upload if present
        $filePath = null;
        if ($request->hasFile('hasil_file')) {
            $file = $request->file('hasil_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            // Store in public disk instead of 'public' path
            $filePath = $file->storeAs('lab_hasil', $fileName, 'public');
        }

        // Create lab hasil record
        $labHasil = LabHasil::create([
            'visitation_id' => $request->visitation_id,
            'asal_lab' => $request->asal_lab,
            'nama_pemeriksaan' => $request->nama_pemeriksaan,
            'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
            'dokter' => $request->dokter,
            'catatan' => $request->catatan,
            'file_path' => $filePath,
            'hasil_detail' => $request->hasil_detail,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hasil lab berhasil diupload',
            'data' => $labHasil
        ]);
    }

    public function getLabHasilDetails($id)
    {
        $labHasil = LabHasil::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $labHasil
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'requests' => 'required|array',
            'requests.*.lab_test_id' => 'required|integer|exists:erm_lab_test,id',
            'requests.*.status' => 'required|in:requested,processing,completed',
            'visitation_id' => 'required', // visitation_id is string
        ]);

        $visitationId = $request->input('visitation_id');
        $requests = $request->input('requests', []);

        $checkedIds = collect($requests)->pluck('lab_test_id')->toArray();

        // 1. Add or update LabPermintaan and Billing
        foreach ($requests as $item) {
            $permintaan = \App\Models\ERM\LabPermintaan::where('visitation_id', $visitationId)
                ->where('lab_test_id', $item['lab_test_id'])
                ->first();
            if ($permintaan) {
                $permintaan->status = $item['status'];
                $permintaan->save();
            } else {
                $permintaan = \App\Models\ERM\LabPermintaan::create([
                    'visitation_id' => $visitationId,
                    'lab_test_id' => $item['lab_test_id'],
                    'status' => $item['status'],
                    'dokter_id' => auth()->id(),
                ]);
                // Create billing entry
                $labTest = \App\Models\ERM\LabTest::find($item['lab_test_id']);
                $permintaan->billings()->create([
                    'visitation_id' => $visitationId,
                    'jumlah' => $labTest->harga,
                    'keterangan' => 'Lab: ' . $labTest->nama,
                ]);
            }
        }

        // 2. Remove unchecked LabPermintaan and Billing
        $toDelete = \App\Models\ERM\LabPermintaan::where('visitation_id', $visitationId)
            ->whereNotIn('lab_test_id', $checkedIds)
            ->get();
        foreach ($toDelete as $permintaan) {
            // Delete related billing
            $permintaan->billings()->delete();
            $permintaan->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Permintaan laboratorium berhasil disimpan'
        ]);
    }

    public function getHasilLisData($visitationId)
    {
        // Get patient ID from visitation
        $visitation = Visitation::findOrFail($visitationId);
        $pasienId = $visitation->pasien_id;
        
        // Get all visitations for this patient to show all lab results
        $visitationIds = Visitation::where('pasien_id', $pasienId)->pluck('id')->toArray();
        
        $query = HasilLis::whereIn('visitation_id', $visitationIds)
                ->join('erm_visitations', 'erm_hasil_lis.visitation_id', '=', 'erm_visitations.id')
                ->leftJoin('erm_dokters', 'erm_visitations.dokter_id', '=', 'erm_dokters.id')
                ->leftJoin('users', 'erm_dokters.user_id', '=', 'users.id')
                ->select(
                    'erm_hasil_lis.kode',
                    'erm_hasil_lis.visitation_id',
                    
                    'erm_hasil_lis.kode_lis',
                    'erm_hasil_lis.header',
                    'erm_hasil_lis.sub_header',
                    'erm_hasil_lis.nama_test',
                    'erm_hasil_lis.hasil',
                    'erm_hasil_lis.flag',
                    'erm_hasil_lis.metode',
                    'erm_hasil_lis.nilai_rujukan',
                    'erm_hasil_lis.satuan',
                    'erm_visitations.tanggal_visitation',
                    'users.name as nama_dokter'
                )
                ->orderBy('erm_visitations.tanggal_visitation', 'desc')
                ->groupBy(
                    'erm_hasil_lis.kode',
                    'erm_hasil_lis.visitation_id',
                    
                    'erm_hasil_lis.kode_lis',
                    'erm_hasil_lis.header',
                    'erm_hasil_lis.sub_header',
                    'erm_hasil_lis.nama_test',
                    'erm_hasil_lis.hasil',
                    'erm_hasil_lis.flag',
                    'erm_hasil_lis.metode',
                    'erm_hasil_lis.nilai_rujukan',
                    'erm_hasil_lis.satuan',
                    'erm_visitations.tanggal_visitation',
                    'users.name'
                ); // Include all selected columns in the GROUP BY
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', function($row) {
                return date('d-m-Y', strtotime($row->tanggal_visitation));
            })
            ->addColumn('dokter', function($row) {
                return $row->nama_dokter ?? 'Tidak ada dokter';
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-view-hasil-lis" data-id="'.$row->visitation_id.'">
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
}