<?php

namespace App\Http\Controllers\ERM;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Models\Area\Province;
use App\Models\ERM\Visitation;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $pasiens = Pasien::select('id', 'nama', 'nik', 'alamat', 'no_hp');

            return DataTables::of($pasiens)
                ->addColumn('actions', function ($user) {
                    return '
                    <a href="' . route('erm.pasiens.edit', $user->id) . '" class="btn btn-warning btn-sm">Edit</a>
                    <form method="POST" action="' . route('erm.pasiens.destroy', $user->id) . '" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    </form>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('erm.pasiens.index');
    }

    public function create()
    {
        $metodeBayar = MetodeBayar::all(); // ambil semua data metode bayar
        $dokters = Dokter::with('spesialisasi')->get(); // ambil semua dokter
        $provinces = Province::all(); // Bisa langsung akses model
        return view('erm.pasiens.create', compact('metodeBayar', 'dokters', 'provinces'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|max:16|unique:erm_pasiens,nik',
            'nama' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'agama' => 'nullable',
            'marital_status' => 'nullable',
            'pendidikan' => 'nullable',
            'pekerjaan' => 'nullable',
            'gol_darah' => 'nullable',
            'alamat' => 'required',
            'no_hp' => 'required|string|max:15',
            'email' => 'nullable|email',
            'instagram' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lastId = DB::table('erm_pasiens')->max('id');
        $newId = $lastId ? str_pad((int)$lastId + 1, 6, '0', STR_PAD_LEFT) : '000001';

        $userId = auth()->id();

        $pasien = Pasien::updateOrCreate(
            ['nik' => $request->nik],
            [
                'id' => $newId,
                'nama' => $request->nama,
                'tanggal_lahir' => $request->tanggal_lahir,
                'gender' => $request->gender,
                'agama' => $request->agama,
                'marital_status' => $request->marital_status,
                'pendidikan' => $request->pendidikan,
                'pekerjaan' => $request->pekerjaan,
                'gol_darah' => $request->gol_darah,
                'notes' => $request->notes,
                'alamat' => $request->alamat,
                'no_hp' => $request->no_hp,
                'no_hp2' => $request->no_hp2,
                'email' => $request->email,
                'instagram' => $request->instagram,
                'user_id' => $userId,
            ]
        );

        $message = ($pasien->wasRecentlyCreated)
            ? 'Data Pasien berhasil dibuat.'
            : 'Data Pasien berhasil diperbarui.';

        // return response()->json([
        //     'status' => 'success',
        //     'message' => $message,
        // ]);
        return response()->json([
            'message' => 'Pasien berhasil ditambahkan.',
            'pasien' => [
                'id' => $pasien->id,
                'nama' => $pasien->nama,
                // add more fields if needed
            ]
        ]);
    }


    public function show(Pasien $pasien)
    {
        return view('erm.pasiens.show', compact('pasien'));
    }

    public function edit(Pasien $pasien)
    {
        return view('erm.pasiens.edit', compact('pasien'));
    }

    public function destroy(Pasien $pasien)
    {
        $pasien->delete();
        return redirect()->route('erm.pasiens.index')->with('success', 'Pasien deleted successfully.');
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
}
