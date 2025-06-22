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
                    'erm_pasiens.id as no_rm',
                    'erm_pasiens.no_hp as telepon_pasien',
                    'erm_pasiens.gender as gender',
                'erm_pasiens.tanggal_lahir as tanggal_lahir'
                )
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                ->where('jenis_kunjungan', 1);

            // Filter by logged-in doctor's ID if the user is a doctor
            $user = Auth::user();
            if ($user->hasRole('Dokter')) {
                $dokter = Dokter::where('user_id', $user->id)->first();
                if ($dokter) {
                    $visitations->where('dokter_id', $dokter->id);
                }
            }

            // Date range filter
            if ($request->start_date && $request->end_date) {
                $visitations->whereDate('tanggal_visitation', '>=', $request->start_date)
                    ->whereDate('tanggal_visitation', '<=', $request->end_date);
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
                ->addColumn('tanggal', function ($v) {
                    // Convert to Indonesian date format: 1 Januari 2025
                    $date = \Carbon\Carbon::parse($v->tanggal_visitation);
                    setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');
                    return $date->translatedFormat('j F Y');
                })
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

                    // Only show reschedule button for Pendaftaran or Perawat roles
                    $additionalBtns = '';
                if ($user->hasRole('Pendaftaran') || $user->hasRole('Perawat')) {
                    $additionalBtns .= '<button class="btn btn-sm btn-warning ml-1" onclick="openRescheduleModal(' . $v->id . ', `' . $v->nama_pasien . '`, ' . $v->pasien_id . ')">Jadwal Ulang</button>';
                    
                    // Add the konfirmasi kunjungan button with gender and birth date
                    $dokterNama = $v->dokter->user->name ?? 'Dokter';
                    $tanggalKunjungan = \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                    
                    // Include gender and tanggal_lahir in the function call
                    $additionalBtns .= '<button class="btn btn-sm btn-success ml-1" onclick="openKonfirmasiModal(`' . 
                        $v->nama_pasien . '`, `' . 
                        $v->telepon_pasien . '`, `' . 
                        $dokterNama . '`, `' . 
                        $tanggalKunjungan . '`, `' .
                        $v->no_antrian . '`, `' .
                        $v->gender . '`, `' .
                        $v->tanggal_lahir . '`)">Konfirmasi Kunjungan</button>';
                }

                return $dokumenBtn . ' ' . $additionalBtns;
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

        // Get the current max antrian number for the date and doctor
        $max = Visitation::whereDate('tanggal_visitation', $tanggal)
            ->where('dokter_id', $dokter_id)
            ->max('no_antrian');

        // Numbers to skip
        $skip = [3, 5];
        $next = ($max ?? 0) + 1;
        while (in_array($next, $skip)) {
            $next++;
        }

        return response()->json([
            'no_antrian' => $next
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
                'status_kunjungan' => 7
            ]);
        }

        // buat kunjungan baru
        Visitation::create([
            'pasien_id' => $request->pasien_id,
            'dokter_id' => $request->dokter_id,
            'tanggal_visitation' => $request->tanggal_visitation,
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id ?? 1,
            'status_kunjungan' => 0,
        ]);

        return response()->json(['message' => 'Berhasil menjadwalkan ulang pasien.']);
    }
}
