<?php

namespace App\Http\Controllers\ERM;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
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
            $pasiens = Pasien::select('id', 'nama', 'nik', 'alamat', 'no_hp', 'status_pasien', 'status_akses');

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
                ->addColumn('status_pasien', function ($user) {
                    // Status pasien configuration (exclude Regular from display)
                    $statusConfig = [
                        'VIP' => ['color' => '#FFD700', 'icon' => 'fas fa-crown', 'title' => 'VIP Member'],
                        'Familia' => ['color' => '#32CD32', 'icon' => 'fas fa-users', 'title' => 'Familia Member'],
                        'Black Card' => ['color' => '#2F2F2F', 'icon' => 'fas fa-credit-card', 'title' => 'Black Card Member']
                    ];
                    
                    $status = $user->status_pasien ?? 'Regular';
                    
                    // Create clickable status display
                    $statusDisplay = '<div class="d-flex align-items-center">';
                    
                    // Only show icon for non-Regular status
                    if ($status !== 'Regular' && isset($statusConfig[$status])) {
                        $config = $statusConfig[$status];
                        $statusDisplay .= '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center mr-2" 
                                              style="width: 20px; height: 20px; background-color: ' . $config['color'] . '; border-radius: 3px;" 
                                              title="' . $config['title'] . '">
                                              <i class="' . $config['icon'] . ' text-white" style="font-size: 11px;"></i>
                                          </span>';
                    }
                    
                    $statusDisplay .= '<span class="status-text">' . $status . '</span>';
                    $statusDisplay .= '<button class="btn btn-sm btn-link p-0 ml-2 edit-status-btn" 
                                          data-pasien-id="' . $user->id . '" 
                                          data-current-status="' . $status . '" 
                                          title="Edit Status">
                                          <i class="fas fa-edit text-primary"></i>
                                      </button>';
                    $statusDisplay .= '</div>';
                    
                    return $statusDisplay;
                })
                ->addColumn('status_akses', function ($user) {
                    $status = $user->status_akses ?? 'normal';
                    
                    // Create clickable status display
                    $statusDisplay = '<div class="d-flex align-items-center">';
                    
                    // Only show wheelchair icon for 'akses cepat' status
                    if ($status === 'akses cepat') {
                        $statusDisplay .= '<span class="status-akses-icon d-inline-flex align-items-center justify-content-center mr-2" 
                                              style="width: 20px; height: 20px; background-color: #007BFF; border-radius: 3px;" 
                                              title="Akses Cepat">
                                              <i class="fas fa-wheelchair text-white" style="font-size: 11px;"></i>
                                          </span>';
                    }
                    
                    $statusDisplay .= '<span class="status-text">' . ucfirst($status) . '</span>';
                    $statusDisplay .= '<button class="btn btn-sm btn-link p-0 ml-2 edit-status-akses-btn" 
                                          data-pasien-id="' . $user->id . '" 
                                          data-current-status="' . $status . '" 
                                          title="Edit Status Akses">
                                          <i class="fas fa-edit text-primary"></i>
                                      </button>';
                    $statusDisplay .= '</div>';
                    
                    return $statusDisplay;
                })
                ->addColumn('actions', function ($user) {
                    return '
                <div class="btn-group-vertical w-100 mb-1">
                    <div class="btn-group mb-1">
                        <a href="javascript:void(0);" 
                           class="btn btn-sm btn-success btn-daftar-visitation" 
                           data-id="' . $user->id . '" 
                           data-nama="' . e($user->nama) . '">
                           <i class="fas fa-calendar-plus mr-1"></i> Buat Kunjungan
                        </a>
                        <a href="javascript:void(0);" 
                            class="btn btn-sm btn-info btn-info-pasien" 
                            data-id="' . $user->id . '">
                            <i class="fas fa-info-circle mr-1"></i> Info Pasien
                        </a>
                        
                    </div>
                    <div class="btn-group">
                        <a href="javascript:void(0);" 
                           class="btn btn-sm btn-primary btn-daftar-lab" 
                           data-id="' . $user->id . '" 
                           data-nama="' . e($user->nama) . '">
                           <i class="fas fa-flask mr-1"></i> Daftar Lab
                        </a>
                        <a href="javascript:void(0);" 
                           class="btn btn-sm btn-warning btn-daftar-produk" 
                           data-id="' . $user->id . '" 
                           data-nama="' . e($user->nama) . '">
                           <i class="fas fa-shopping-cart mr-1"></i> Beli Produk
                        </a>
                        
                    </div>
                </div>';
                })
                ->rawColumns(['status_pasien', 'status_akses', 'actions'])
                ->make(true);
        }

        $metodeBayar = MetodeBayar::all();
        $dokters = Dokter::with('spesialisasi')->get();
        $kliniks = Klinik::all();

        $pasienName = '';

        return view('erm.pasiens.index', compact('metodeBayar', 'dokters', 'pasienName', 'kliniks'));
    }

    public function create(Request $request)
{
    $metodeBayar = MetodeBayar::all();
    $dokters = Dokter::with('spesialisasi')->get();
    $kliniks = Klinik::all();
    $provinces = Province::all();
    
    // Check if we're editing an existing patient
    $pasien = null;
    $isEditing = false;
    
    if ($request->has('edit_id')) {
        $pasien = Pasien::with('village')->find($request->edit_id);
        $isEditing = true;
    }
    
    return view('erm.pasiens.create', compact(
        'metodeBayar', 
        'dokters', 
        'provinces', 
        'kliniks', 
        'pasien', 
        'isEditing'
    ));
}

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nik' => 'nullable|string|max:16|unique:erm_pasiens,nik,' . $request->pasien_id . ',id',
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
        'status_pasien' => 'nullable|in:Regular,VIP,Familia,Black Card',
        'status_akses' => 'nullable|in:normal,akses cepat',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $userId = Auth::id();

    DB::beginTransaction();

    try {
        $pasienId = $request->pasien_id;
        
        // Check if we're updating or creating
        if (!empty($pasienId)) {
            // Update existing patient
            $pasien = Pasien::findOrFail($pasienId);
            $pasien->update([
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
                'village_id' => $request->village,
                'no_hp' => $request->no_hp,
                'no_hp2' => $request->no_hp2,
                'email' => $request->email,
                'instagram' => $request->instagram,
                'status_pasien' => $request->status_pasien ?? 'Regular',
                'status_akses' => $request->status_akses ?? 'normal',
                'user_id' => $userId,
            ]);
        } else {
            // Create new patient
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
                'village_id' => $request->village,
                'no_hp' => $request->no_hp,
                'no_hp2' => $request->no_hp2,
                'email' => $request->email,
                'instagram' => $request->instagram,
                'status_pasien' => $request->status_pasien ?? 'Regular',
                'status_akses' => $request->status_akses ?? 'normal',
                'user_id' => $userId,
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => !empty($pasienId) ? 'Data pasien berhasil diperbarui.' : 'Pasien berhasil ditambahkan.',
            'pasien' => [
                'id' => $pasien->id,
                'nama' => $pasien->nama,
            ]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => !empty($pasienId) ? 'Gagal memperbarui data pasien' : 'Gagal menambahkan pasien',
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

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_pasien' => 'required|in:Regular,VIP,Familia,Black Card'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pasien = Pasien::findOrFail($id);
            $pasien->status_pasien = $request->status_pasien;
            $pasien->save();

            return response()->json([
                'success' => true,
                'message' => 'Status pasien berhasil diperbarui',
                'data' => [
                    'id' => $pasien->id,
                    'status_pasien' => $pasien->status_pasien
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status pasien'
            ], 500);
        }
    }

    public function updateStatusAkses(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_akses' => 'required|in:normal,akses cepat'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pasien = Pasien::findOrFail($id);
            $pasien->status_akses = $request->status_akses;
            $pasien->save();

            return response()->json([
                'success' => true,
                'message' => 'Status akses pasien berhasil diperbarui',
                'data' => [
                    'id' => $pasien->id,
                    'status_akses' => $pasien->status_akses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status akses pasien'
            ], 500);
        }
    }

    public function updateStatusCombined(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_pasien' => 'required|in:Regular,VIP,Familia,Black Card',
            'status_akses' => 'required|in:normal,akses cepat'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pasien = Pasien::findOrFail($id);
            $pasien->status_pasien = $request->status_pasien;
            $pasien->status_akses = $request->status_akses;
            $pasien->save();

            return response()->json([
                'success' => true,
                'message' => 'Status pasien berhasil diperbarui',
                'data' => [
                    'id' => $pasien->id,
                    'status_pasien' => $pasien->status_pasien,
                    'status_akses' => $pasien->status_akses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status pasien'
            ], 500);
        }
    }
}
