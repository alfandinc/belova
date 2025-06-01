<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;

class RawatJalanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])
                ->select(
                    'erm_visitations.*',
                    'erm_pasiens.nama as nama_pasien',
                    'erm_pasiens.id as no_rm'
                )
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id');

            // Filter by logged-in doctor's ID if the user is a doctor
            $user = Auth::user();
            if ($user->hasRole('Dokter')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $visitations->where('dokter_id', $dokter->id);
                }
            }

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }
            if ($request->dokter_id) {
                $visitations->where('dokter_id', $request->dokter_id);
            }

            return datatables()->of($visitations)
                ->filterColumn('nama_pasien', function ($query, $keyword) {
                    $query->where('erm_pasiens.nama', 'like', "%{$keyword}%");
                })
                ->filterColumn('no_rm', function ($query, $keyword) {
                    $query->where('erm_pasiens.id', 'like', "%{$keyword}%");
                })
                ->addColumn('antrian', function ($v) {
                    return '<span data-order="' . intval($v->no_antrian) . '">' . $v->no_antrian . '</span>';
                })
                ->addColumn('no_rm', fn($v) => $v->no_rm ?? '-') // Use the aliased column
                ->addColumn('nama_pasien', fn($v) => $v->nama_pasien ?? '-') // Use the aliased column
                ->addColumn('tanggal', fn($v) => $v->tanggal_visitation)
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $dokumenBtn = '';

                    if ($user->hasRole('Perawat')) {
                        $url = route('erm.asesmenperawat.create', $v->id);
                        $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-info">Lihat</a>';
                    } elseif ($user->hasRole('Dokter')) {
                        if ($v->status_dokumen === 'asesmen') {
                            $url = route('erm.asesmendokter.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-primary">Asesmen</a>';
                        } elseif ($v->status_dokumen === 'cppt') {
                            $url = route('erm.cppt.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-success">CPPT</a>';
                        }
                    }

                    $rescheduleBtn = '<button class="btn btn-sm btn-warning ml-1" onclick="openRescheduleModal(' . $v->id . ', `' . $v->nama_pasien . '`, ' . $v->pasien_id . ')">Jadwal Ulang</button>';

                    return $dokumenBtn . ' ' . $rescheduleBtn;
                })
                ->rawColumns(['antrian', 'dokumen'])
                ->make(true);
        }

        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        $role = Auth::user()->getRoleNames()->first();
        return view('erm.rawatjalans.index', compact('dokters', 'metodeBayar', 'role'));
    }

    public function cekAntrian(Request $request)
    {
        $tanggal = $request->tanggal;
        $dokter_id = $request->dokter_id;

        $count = Visitation::whereDate('tanggal_visitation', $tanggal)
            ->where('dokter_id', $dokter_id)
            ->count();

        return response()->json([
            'no_antrian' => $count + 1
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'dokter_id' => 'required',
            'tanggal_visitation' => 'required|date',
            'no_antrian' => 'required|integer',
        ]);

        // update kunjungan lama jadi progress 7
        if ($request->has('visitation_id')) {
            Visitation::where('id', $request->visitation_id)->update([
                'progress' => 7
            ]);
        }

        // buat kunjungan baru
        Visitation::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id ?? 1,
            'progress' => 1,
            'status_dokumen' => 'belum',
        ]);

        return response()->json(['message' => 'Berhasil menjadwalkan ulang pasien.']);
    }
}
