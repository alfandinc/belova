<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;
use App\Models\ERM\MetodeBayar;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VisitationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $pasiens = Pasien::select('id', 'nama', 'nik', 'alamat', 'no_hp');

            return DataTables::of($pasiens)
                ->addColumn('actions', function ($user) {
                    return '
                    <a href="javascript:void(0);" 
                       class="btn btn-sm btn-primary btn-daftar-visitation" 
                       data-id="' . $user->id . '" 
                       data-nama="' . e($user->nama) . '">
                       Daftarkan Kunjungan
                    </a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $metodeBayar = MetodeBayar::all(); // ambil semua data metode bayar
        return view('erm.visitations.index', compact('metodeBayar'));
    }

    public function create()
    {
        $pasiens = Pasien::all();
        return view('erm.visitations.create', compact('pasiens'));
    }

    public function store(Request $request)
    {
        // \Log::info($request->all()); // Tambahkan log ini untuk cek data terkirim
        $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'dokter_id' => 'required|string',
            'tanggal_visitation' => 'required|date',
            'metode_bayar_id' => 'required',
        ]);

        Visitation::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'metode_bayar_id' => $request->metode_bayar_id,
            'status' => 'asesmen',
            'progress' => 1
        ]);

        return response()->json(['success' => true, 'message' => 'Kunjungan berhasil disimpan.']);
    }
}
