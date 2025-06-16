<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\ERM\LabKategori;
use App\Models\ERM\LabPermintaan;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\ERM\LabTest;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ElabController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $labCategories = LabKategori::orderBy('nama')->get();
        
        // Get total estimated price
        $totalHarga = LabPermintaan::where('visitation_id', $visitationId)
                        ->with('labTest')
                        ->get()
                        ->sum(function($item) {
                            return $item->labTest->harga;
                        });

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.elab.create', array_merge([
            'visitation' => $visitation,
            'labCategories' => $labCategories,
            'totalHarga' => $totalHarga
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
            'lab_test_id' => 'required|exists:erm_lab_test,id'
        ]);

        $labRequest = LabPermintaan::create([
            'visitation_id' => $request->visitation_id,
            'lab_test_id' => $request->lab_test_id,
            'status' => 'requested',
            'dokter_id' => Auth::id()
        ]);

        $labRequest->load(['labTest.labKategori', 'dokter']);

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
            LabPermintaan::whereIn('id', $request->ids)->delete();
            
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
}