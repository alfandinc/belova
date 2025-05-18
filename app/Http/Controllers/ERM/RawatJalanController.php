<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use Illuminate\Support\Facades\DB;

class RawatJalanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $visitations = Visitation::with(['pasien', 'metodeBayar'])->select('erm_visitations.*');

            if ($request->tanggal) {
                $visitations->whereDate('tanggal_visitation', $request->tanggal);
            }
            if ($request->dokter_id) {
                $visitations->where('dokter_id', $request->dokter_id);
            }

            $user = Auth::user();
            if ($user->hasRole('Perawat')) {
                $visitations->where('progress', 1);
            } elseif ($user->hasRole('Dokter')) {
                $dokter = \App\Models\ERM\Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $visitations->where('dokter_id', $dokter->id)
                        ->whereIn('progress', [2, 3]);
                } else {
                    // Optional: return empty if no dokter found
                    $visitations->whereRaw('1 = 0');
                }
            }

            return datatables()->of($visitations)
                ->addColumn('antrian', function ($v) {
                    return '<span data-order="' . intval($v->no_antrian) . '">' . $v->no_antrian . '</span>';
                })
                ->addColumn('no_rm', fn($v) => $v->pasien->id ?? '-')
                ->addColumn('nama_pasien', fn($v) => $v->pasien->nama ?? '-')
                ->addColumn('tanggal', fn($v) => $v->tanggal_visitation)
                // ->addColumn('status_dokumen', fn($v) => ucfirst($v->status_dokumen))
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('progress', fn($v) => $v->progress) // 🛠️ Tambah kolom progress!
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

                    $rescheduleBtn = '<button class="btn btn-sm btn-warning ml-1" onclick="openRescheduleModal(' . $v->id . ', `' . $v->pasien->nama . '`, ' . $v->pasien_id . ')">Jadwal Ulang</button>';

                    return $dokumenBtn . ' ' . $rescheduleBtn;
                })
                ->rawColumns(['antrian', 'dokumen'])
                ->make(true);
        }

        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        $role = Auth::user()->getRoleNames()->first(); // or ->pluck('name')[0]
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
