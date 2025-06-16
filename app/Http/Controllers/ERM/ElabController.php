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

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);

        return view('erm.elab.create', array_merge([
            'visitation' => $visitation,
            'labCategories' => $labCategories
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
                            data-kategori="'.$row->labKategori->nama.'">
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
            ->addColumn('hasil_text', function($row) {
                if ($row->status == 'completed') {
                    return $row->hasil;
                } else {
                    return '<span class="text-muted">Menunggu hasil</span>';
                }
            })
            ->rawColumns(['status_label', 'hasil_text'])
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

        return response()->json([
            'success' => true,
            'message' => 'Permintaan lab berhasil dibuat',
            'data' => $labRequest
        ]);
    }
}