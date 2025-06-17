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
use Yajra\DataTables\Facades\DataTables;

class EradiologiController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $radiologiCategories = RadiologiKategori::orderBy('nama')->get();

        // Get total estimated price
        $totalHarga = RadiologiPermintaan::where('visitation_id', $visitationId)
                        ->with('radiologiTest')
                        ->get()
                        ->sum(function($item) {
                            return $item->radiologiTest->harga;
                        });

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.eradiologi.create', array_merge([
            'visitation' => $visitation,
            'radiologiCategories' => $radiologiCategories,
            'totalHarga' => $totalHarga
        ], $pasienData, $createKunjunganData));
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
    
    return DataTables::of($query)
        ->addIndexColumn() // Adds row numbers
        ->addColumn('tanggal', function($row) {
            return $row->tanggal_pemeriksaan->format('d-m-Y');
        })
        ->addColumn('action', function($row) {
            $viewBtn = '<button class="btn btn-sm btn-info btn-view-radiologi" data-id="'.$row->id.'">
                            <i class="fas fa-eye"></i> Lihat
                        </button>';
            return $viewBtn;
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

public function getRadiologiHasilDetails($id)
{
    $radiologiHasil = RadiologiHasil::findOrFail($id);
    
    return response()->json([
        'success' => true,
        'data' => $radiologiHasil
    ]);
}
}