<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Dokter;
use App\Models\ERM\Klinik;

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
                    'erm_pasiens.tanggal_lahir as tanggal_lahir',
                    'erm_pasiens.status_pasien as status_pasien',
                    'erm_pasiens.status_akses as status_akses'
                )
                ->leftJoin('erm_pasiens', 'erm_visitations.pasien_id', '=', 'erm_pasiens.id')
                ->whereIn('jenis_kunjungan', [1, 2])
                ->where('status_kunjungan', '!=', 7); // Exclude cancelled visits

            // Filter by klinik_id if provided
            if ($request->klinik_id) {
                $visitations->where('klinik_id', $request->klinik_id);
            }

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
                    $antrianHtml = '<span data-order="' . intval($v->no_antrian) . '">' . $v->no_antrian . '</span>';
                    // Check if visitation has labpermintaan
                    if ($v->labPermintaan && $v->labPermintaan->count() > 0) {
                        $antrianHtml .= ' <span class="lab-icon d-inline-flex align-items-center justify-content-center" title="Ada Permintaan Lab" style="width: 20px; height: 20px; background-color: #17a2b8; border-radius: 3px; margin-left: 12px; color: #fff;"><i class="fas fa-flask" style="font-size: 11px;"></i></span>';
                    }
                    if ($v->riwayatTindakan && $v->riwayatTindakan->count() > 0) {
                        $antrianHtml .= ' <span class="tindakan-icon d-inline-flex align-items-center justify-content-center" title="Ada Tindakan" style="width: 20px; height: 20px; background-color: #28a745; border-radius: 3px; margin-left: 12px; color: #fff;"><i class="fas fa-stethoscope" style="font-size: 11px;"></i></span>';
                    }
                    return $antrianHtml;
                })
                ->addColumn('no_rm', fn($v) => $v->no_rm ?? '-') // Use the aliased column
                ->addColumn('nama_pasien', function ($v) {
                    $nama = $v->nama_pasien ?? '-';
                    $icons = '';
                    
                    // Status pasien configuration (exclude Regular from display)
                    $statusConfig = [
                        'VIP' => ['color' => '#FFD700', 'icon' => 'fas fa-crown', 'title' => 'VIP Member'],
                        'Familia' => ['color' => '#32CD32', 'icon' => 'fas fa-users', 'title' => 'Familia Member'],
                        'Black Card' => ['color' => '#2F2F2F', 'icon' => 'fas fa-credit-card', 'title' => 'Black Card Member']
                    ];
                    
                    // Add status_pasien icon if not Regular
                    $status = $v->status_pasien ?? 'Regular';
                    if ($status !== 'Regular' && isset($statusConfig[$status])) {
                        $config = $statusConfig[$status];
                        $icons .= '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center" 
                                      style="width: 20px; height: 20px; background-color: ' . $config['color'] . '; border-radius: 3px; margin-right: 8px;" 
                                      title="' . $config['title'] . '">
                                      <i class="' . $config['icon'] . ' text-white" style="font-size: 11px;"></i>
                                  </span>';
                    }
                    
                    // Add status_akses icon if akses cepat
                    $statusAkses = $v->status_akses ?? 'normal';
                    if ($statusAkses === 'akses cepat') {
                        $icons .= '<span class="status-akses-icon d-inline-flex align-items-center justify-content-center" 
                                      style="width: 20px; height: 20px; background-color: #007BFF; border-radius: 3px; margin-right: 8px;" 
                                      title="Akses Cepat">
                                      <i class="fas fa-wheelchair text-white" style="font-size: 11px;"></i>
                                  </span>';
                    }
                    
                    // Return icons + name
                    return $icons . $nama;
                }) // Use the aliased column
                ->addColumn('tanggal', function ($v) {
                    // Convert to Indonesian date format: 1 Januari 2025
                    $date = \Carbon\Carbon::parse($v->tanggal_visitation);
                    setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');
                    return $date->translatedFormat('j F Y');
                })
                ->addColumn('metode_bayar', fn($v) => $v->metodeBayar->nama ?? '-')
                ->addColumn('spesialisasi', function ($v) {
                    return $v->dokter && $v->dokter->spesialisasi ? $v->dokter->spesialisasi->nama : '-';
                })
                ->addColumn('dokter_nama', function ($v) {
                    return $v->dokter && $v->dokter->user ? $v->dokter->user->name : '-';
                })
                ->addColumn('dokumen', function ($v) {
                    $user = Auth::user();
                    $dokumenBtn = '';

                    if ($user->hasRole('Perawat')) {
                        $url = route('erm.asesmenperawat.create', $v->id);
                        $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-primary ml-1" style="font-weight:bold;" title="Lihat"><i class="fas fa-eye mr-1"></i>Lihat</a>';
                    } elseif ($user->hasRole('Dokter')) {
                        if ($v->status_dokumen === 'asesmen') {
                            $url = route('erm.asesmendokter.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-primary ml-1" style="font-weight:bold;" title="Asesmen"><i class="fas fa-user-md mr-1"></i>Asesmen</a>';
                        } elseif ($v->status_dokumen === 'cppt') {
                            $url = route('erm.cppt.create', $v->id);
                            $dokumenBtn = '<a href="' . $url . '" class="btn btn-sm btn-success ml-1" style="font-weight:bold;" title="CPPT"><i class="fas fa-notes-medical mr-1"></i>CPPT</a>';
                        }
                    }

                    // Only show reschedule button for Pendaftaran or Perawat roles
                    $additionalBtns = '';
                    // Remove Jadwal Ulang button
                    // if ($user->hasRole('Pendaftaran') || $user->hasRole('Perawat')) {
                    //     $additionalBtns .= '<button class="btn btn-sm btn-warning ml-1" onclick="openRescheduleModal(' . $v->id . ', `' . $v->nama_pasien . '`, ' . $v->pasien_id . ')">Jadwal Ulang</button>';
                    //     // Add the konfirmasi kunjungan button with gender and birth date
                    //     $dokterNama = $v->dokter->user->name ?? 'Dokter';
                    //     $tanggalKunjungan = \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                    //     $additionalBtns .= '<button class="btn btn-sm btn-success ml-1" onclick="openKonfirmasiModal(`' . 
                    //         $v->nama_pasien . '`, `' . 
                    //         $v->telepon_pasien . '`, `' . 
                    //         $dokterNama . '`, `' . 
                    //         $tanggalKunjungan . '`, `' .
                    //         $v->no_antrian . '`, `' .
                    //         $v->gender . '`, `' .
                    //         $v->tanggal_lahir . '`)">Konfirmasi Kunjungan</button>';
                    // }
                    if ($user->hasRole('Pendaftaran') || $user->hasRole('Perawat')) {
                        $dokterNama = $v->dokter->user->name ?? 'Dokter';
                        $tanggalKunjungan = \Carbon\Carbon::parse($v->tanggal_visitation)->translatedFormat('j F Y');
                        // WA Pasien button (icon only)
                        $additionalBtns .= '<button class="btn btn-sm btn-success ml-1" style="font-weight:bold;" onclick="openKonfirmasiModal(`' .
                            $v->nama_pasien . '`, `' .
                            $v->telepon_pasien . '`, `' .
                            $dokterNama . '`, `' .
                            $tanggalKunjungan . '`, `' .
                            $v->no_antrian . '`, `' .
                            $v->gender . '`, `' .
                            $v->tanggal_lahir . '` )" title="WA Pasien"><i class=\'fab fa-whatsapp\'></i></button>';
                        // Edit Antrian button (icon only)
                        $additionalBtns .= '<button class="btn btn-sm btn-info ml-1" style="font-weight:bold;" onclick="editAntrian(\'' . $v->id . '\', ' . $v->no_antrian . ', \'' . $v->waktu_kunjungan . '\')" title="Edit Antrian"><i class=\'fas fa-edit\'></i></button>';
                        // Batalkan button (icon only)
                        $additionalBtns .= '<button class="btn btn-sm btn-danger ml-1" style="font-weight:bold;" onclick="batalkanKunjungan(\'' . $v->id . '\', this)" title="Batalkan"><i class=\'fas fa-times\'></i></button>';
                    }

                    return $dokumenBtn . ' ' . $additionalBtns;
                })
                ->addColumn('selesai_asesmen', function ($v) {
                    $asesmenPenunjang = $v->asesmenPenunjang;
                    $cppt = $v->cppt;
                    if ($asesmenPenunjang && $asesmenPenunjang->created_at) {
                        return $asesmenPenunjang->created_at->format('H:i');
                    } elseif ($cppt && $cppt->created_at) {
                        return $cppt->created_at->format('H:i');
                    } else {
                        return '-';
                    }
                })
                ->addColumn('waktu_kunjungan', function ($v) {
                    return $v->waktu_kunjungan ? substr($v->waktu_kunjungan, 0, 5) : '-';
                })
                ->rawColumns(['antrian', 'nama_pasien', 'dokumen'])
                ->make(true);
        }

        $dokters = Dokter::with('user', 'spesialisasi')->get();
        $metodeBayar = MetodeBayar::all();
        $kliniks = Klinik::all();
        $role = Auth::user()->getRoleNames()->first();
        return view('erm.rawatjalans.index', compact('dokters', 'metodeBayar', 'role', 'kliniks'));
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
            'waktu_kunjungan' => 'nullable|date_format:H:i', // validate waktu_kunjungan
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
            'waktu_kunjungan' => $request->waktu_kunjungan, // save waktu_kunjungan
            'no_antrian' => $request->no_antrian,
            'metode_bayar_id' => $request->metode_bayar_id ?? 1,
            'status_kunjungan' => 0,
        ]);

        return response()->json(['message' => 'Berhasil menjadwalkan ulang pasien.']);
    }

    // Batalkan kunjungan
    public function batalkan(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
        ]);
        $visitation = Visitation::findOrFail($request->visitation_id);
        $visitation->status_kunjungan = 7;
        $visitation->no_antrian = null;
        $visitation->save();
        return response()->json(['success' => true]);
    }

    // Edit antrian
    public function editAntrian(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            'no_antrian' => 'required|integer|min:1',
            'waktu_kunjungan' => 'nullable|date_format:H:i', // allow editing waktu_kunjungan
        ]);
        $visitation = Visitation::findOrFail($request->visitation_id);
        $visitation->no_antrian = $request->no_antrian;
        if ($request->has('waktu_kunjungan')) {
            $visitation->waktu_kunjungan = $request->waktu_kunjungan;
        }
        $visitation->save();
        return response()->json(['success' => true]);
    }
}
