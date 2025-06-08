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
use App\Models\ERM\Klinik;

class PasienController extends Controller
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
                   class="btn btn-sm btn-success btn-daftar-visitation" 
                   data-id="' . $user->id . '" 
                   data-nama="' . e($user->nama) . '">
                   Buat Kunjungan
                </a>
                <a href="javascript:void(0);" 
                    class="btn btn-sm btn-primary btn-info-pasien" 
                    data-id="' . $user->id . '">
                    Info
                </a>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $metodeBayar = MetodeBayar::all();
        $dokters = Dokter::with('spesialisasi')->get();
        $kliniks = Klinik::all();

        $pasienName = '';

        return view('erm.pasiens.index', compact('metodeBayar', 'dokters', 'pasienName'));
    }

    public function create()
    {
        $metodeBayar = MetodeBayar::all();
        $dokters = Dokter::with('spesialisasi')->get();
        $kliniks = Klinik::all();
        $provinces = Province::all();
        return view('erm.pasiens.create', compact('metodeBayar', 'dokters', 'provinces', 'kliniks'));
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

        $userId = auth()->id();

        DB::beginTransaction();

        try {
            // lock table dulu
            $lastId = DB::table('erm_pasiens')
                ->select(DB::raw('MAX(CAST(id AS UNSIGNED)) as max_id'))
                ->lockForUpdate()
                ->value('max_id');

            $newId = $lastId ? str_pad((int)$lastId + 1, 6, '0', STR_PAD_LEFT) : '000001';

            // Insert pasien
            $pasien = Pasien::create([
                'id' => $newId,
                'nik' => $request->nik,
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
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pasien berhasil ditambahkan.',
                'pasien' => [
                    'id' => $pasien->id,
                    'nama' => $pasien->nama,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan pasien',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $pasien = Pasien::with(['village'])->findOrFail($id);

        return response()->json($pasien);
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
