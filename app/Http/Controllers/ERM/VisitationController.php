<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;
use App\Models\ERM\Dokter;
use App\Models\ERM\MetodeBayar;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class VisitationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $pasiens = Pasien::select('id', 'nama', 'nik', 'alamat', 'no_hp');

            if ($request->no_rm) {
                $pasiens->where('id', $request->no_rm);
            }
            if ($request->nama) {
                $pasiens->where('nama', 'like', '%' . $request->nama . '%');
            }
            if ($request->nik) {
                $pasiens->where('nik', 'like', '%' . $request->nik . '%');
            }
            if ($request->alamat) {
                $pasiens->where('alamat', 'like', '%' . $request->alamat . '%');
            }

            return DataTables::of($pasiens)
                ->addColumn('actions', function ($user) {
                    return '
                <a href="javascript:void(0);" 
                   class="btn btn-sm btn-primary btn-daftar-visitation" 
                   data-id="' . $user->id . '" 
                   data-nama="' . e($user->nama) . '">
                   Buat Kunjungan
                </a>
                <a href="javascript:void(0);" 
                    class="btn btn-sm btn-info btn-info-pasien" 
                    data-id="' . $user->id . '">
                    Info
                </a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $metodeBayar = MetodeBayar::all();
        $dokters = Dokter::with('spesialisasi')->get();

        return view('erm.visitations.index', compact('metodeBayar', 'dokters'));
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

        // Buat ID custom
        $customId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);


        Visitation::create([
            'id' => $customId, // <-- pastikan kolom 'id' di DB bisa diisi manual (non auto-increment)
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id,
            'progress' => 1,
            'user_id' => Auth::id(), // Menyimpan ID user yang login
        ]);

        return response()->json(['success' => true, 'message' => 'Kunjungan berhasil disimpan.']);
    }

    public function cekAntrian(Request $request)
    {
        $dokterId = $request->dokter_id;
        $tanggal = $request->tanggal;

        $jumlahKunjungan = Visitation::where('dokter_id', $dokterId)
            ->whereDate('tanggal_visitation', $tanggal)
            ->count();

        return response()->json([
            'no_antrian' => $jumlahKunjungan + 1
        ]);
    }

    public function show($id)
    {
        $pasien = Pasien::with(['village'])->findOrFail($id);

        return response()->json($pasien);
    }
}
