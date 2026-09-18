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
use App\Models\HRD\Employee;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class PasienController extends Controller
{
    /**
     * Lightweight pasien search endpoint for Select2.
     * Returns: { results: [{ id, text }] }
     */
    public function select2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $query = Pasien::query()->select(['id', 'nama', 'identity_document', 'identity_number']);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', '%' . $term . '%')
                    ->orWhere('id', 'like', '%' . $term . '%')
                    ->orWhere('identity_number', 'like', '%' . $term . '%');
            });
        }

        $items = $query
            ->orderBy('nama')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                $label = trim(($p->nama ?? '-') . ' (RM: ' . $p->id . ')');
                if (!empty($p->identity_number)) {
                    $label .= ' - ' . strtoupper($p->identity_document ?? 'ktp') . ': ' . $p->identity_number;
                }
                return [
                    'id' => (string) $p->id,
                    'text' => $label,
                ];
            })
            ->values();

        return response()->json(['results' => $items]);
    }

    public function checkMarketplaceDuplicate(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'referral_detail' => ['required', 'string', Rule::in(Pasien::marketplaceReferralOptions())],
        ]);

        $nama = trim((string) $validated['nama']);
        $referralDetail = strtolower(trim((string) $validated['referral_detail']));

        $pasien = Pasien::query()
            ->select(['id', 'nama', 'no_hp', 'alamat', 'referral_type', 'referral_detail'])
            ->where('referral_type', Pasien::REFERRAL_TYPE_MARKETPLACE)
            ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower($nama)])
            ->whereRaw('LOWER(TRIM(referral_detail)) = ?', [$referralDetail])
            ->orderBy('id')
            ->first();

        if (!$pasien) {
            return response()->json([
                'exists' => false,
                'message' => 'Tidak ditemukan pasien marketplace dengan nama dan referral yang sama.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'message' => 'Sudah ada pasien marketplace dengan nama dan referral yang sama.',
            'pasien' => [
                'id' => $pasien->id,
                'nama' => $pasien->nama,
                'no_hp' => $pasien->no_hp,
                'alamat' => $pasien->alamat,
                'referral_type' => $pasien->referral_type,
                'referral_detail' => $pasien->referral_detail,
            ],
        ]);
    }

    public function checkDuplicateNameBirthdate(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'pasien_id' => 'nullable|string',
        ]);

        $patients = $this->findPatientsWithSameNameAndBirthdate(
            $validated['nama'],
            $validated['tanggal_lahir'],
            $validated['pasien_id'] ?? null
        );

        return response()->json([
            'exists' => $patients->isNotEmpty(),
            'count' => $patients->count(),
            'patients' => $patients->values(),
            'message' => $patients->isNotEmpty()
                ? 'Ditemukan pasien dengan kombinasi nama dan tanggal lahir yang sama.'
                : 'Tidak ditemukan pasien dengan kombinasi nama dan tanggal lahir yang sama.',
        ]);
    }

    public function checkIdentityNumber(Request $request)
    {
        $validated = $request->validate([
            'identity_document' => 'required|in:ktp,sim,paspor,kia',
            'identity_number' => 'required|string|max:50',
            'pasien_id' => 'nullable|string',
        ]);

        $identityNumber = trim((string) $validated['identity_number']);
        $documentType = $validated['identity_document'];

        if ($documentType === 'ktp' && !preg_match('/^\d{16}$/', $identityNumber)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'message' => 'Nomor identitas untuk KTP harus 16 digit angka.',
            ]);
        }

        $pasien = Pasien::query()
            ->select(['id', 'nama', 'identity_document', 'identity_number'])
            ->where('identity_number', $identityNumber)
            ->when(!empty($validated['pasien_id']), function ($query) use ($validated) {
                $query->where('id', '!=', $validated['pasien_id']);
            })
            ->first();

        if (!$pasien) {
            return response()->json([
                'valid' => true,
                'exists' => false,
                'message' => 'Nomor identitas tersedia.',
            ]);
        }

        return response()->json([
            'valid' => false,
            'exists' => true,
            'message' => 'Nomor identitas sudah digunakan pasien lain.',
            'pasien' => [
                'id' => (string) $pasien->id,
                'nama' => $pasien->nama,
                'identity_document' => $pasien->identity_document,
                'identity_number' => $pasien->identity_number,
            ],
        ]);
    }

    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->resolveIndexDateRange($request);
        $shouldApplyAjaxDateFilter = $request->filled('start_date') || $request->filled('end_date');

        if ($request->ajax() && $request->boolean('stats')) {
            return response()->json($this->getPatientIndexStats($startDate, $endDate));
        }

        if ($request->ajax()) {
            // eager-load nested area relations so DataTables payload includes village/district/regency/province
            $pasiens = Pasien::with([
                    'village.district.regency.province',
                    'referralable' => function (MorphTo $morphTo) {
                        $morphTo->morphWith([
                            Pasien::class => [],
                            Employee::class => [],
                            Dokter::class => ['user', 'spesialisasi'],
                            MarketingEvent::class => [],
                        ]);
                    },
                ])
                ->select([
                    'id',
                    'nama',
                    'identity_document',
                    'identity_number',
                    'tanggal_lahir',
                    'notes',
                    'alamat',
                    'village_id',
                    'no_hp',
                    'referral_type',
                    'referral_detail',
                    'referralable_type',
                    'referralable_id',
                    'status_pasien',
                    'status_akses',
                    'status_review',
                    'created_at',
                ])
                ->withMax('visitations', 'tanggal_visitation');

            if ($request->no_rm) {
                $pasiens->where('id', $request->no_rm);
            }
            if ($request->nama) {
                $pasiens->where('nama', 'like', '%' . $request->nama . '%');
            }
            if ($request->nik) {
                $pasiens->where('identity_number', 'like', '%' . $request->nik . '%');
            }
            if ($request->alamat) {
                $pasiens->where('alamat', 'like', '%' . $request->alamat . '%');
            }
            if ($request->status_pasien) {
                $pasiens->where('status_pasien', $request->status_pasien);
            }
            if ($request->referral_type) {
                $pasiens->where('referral_type', $request->referral_type);
            }
            if ($shouldApplyAjaxDateFilter) {
                $pasiens->whereDate('created_at', '>=', $startDate->toDateString())
                    ->whereDate('created_at', '<=', $endDate->toDateString());
            }
            if ($request->status_akses) {
                $pasiens->where('status_akses', $request->status_akses);
            }
            if (isset($request->status_review) && $request->status_review !== '') {
                if ($request->status_review === 'belum') {
                    $pasiens->where(function($q) {
                        $q->whereNull('status_review')->orWhere('status_review', 'belum');
                    });
                } else {
                    $pasiens->where('status_review', $request->status_review);
                }
            }

            return DataTables::of($pasiens)
                ->addColumn('status_pasien_icon', function ($user) {
                    return $this->formatStatusPasienIcon($user->status_pasien);
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
                ->addColumn('status_review', function ($user) {
                    $status = $user->status_review ?? 'belum';

                    $statusDisplay = '<div class="d-flex align-items-center">';

                    // icon for sudah vs belum
                    if ($status === 'sudah') {
                        $statusDisplay .= '<span class="status-review-icon d-inline-flex align-items-center justify-content-center mr-2" '
                            . 'style="width:20px;height:20px;background-color:#28a745;border-radius:3px;">'
                            . '<i class="fas fa-check text-white" style="font-size:11px;"></i>'
                        . '</span>';
                    } else {
                        $statusDisplay .= '<span class="status-review-icon d-inline-flex align-items-center justify-content-center mr-2" '
                            . 'style="width:20px;height:20px;background-color:#6c757d;border-radius:3px;">'
                            . '<i class="fas fa-times text-white" style="font-size:11px;"></i>'
                        . '</span>';
                    }

                    $statusDisplay .= '<span class="status-text text-capitalize mr-2">' . ucfirst($status) . '</span>';
                    $statusDisplay .= '<button class="btn btn-sm btn-link p-0 ml-2 edit-status-review-btn" '
                                    . 'data-pasien-id="' . $user->id . '" '
                                    . 'data-current-status="' . $status . '" '
                                    . 'title="Edit Status Review">'
                                    . '<i class="fas fa-edit text-primary"></i>'
                                . '</button>';

                    $statusDisplay .= '</div>';

                    return $statusDisplay;
                })
                ->addColumn('merchandise', function ($user) {
                    return '<button class="btn btn-sm btn-outline-primary btn-merch-checklist" data-id="' . $user->id . '">Lihat</button>';
                })
                ->addColumn('tanggal_lahir_display', function ($user) {
                    if (empty($user->tanggal_lahir)) {
                        return '-';
                    }

                    $birthDate = Carbon::parse($user->tanggal_lahir);
                    return $this->formatIndonesianDate($birthDate) . ' (' . $birthDate->age . ' th)';
                })
                ->addColumn('referral_display', function ($user) {
                    return $this->formatReferralDisplay($user);
                })
                ->addColumn('last_visit_display', function ($user) {
                    if (empty($user->visitations_max_tanggal_visitation)) {
                        return '-';
                    }

                    return $this->formatIndonesianDate(Carbon::parse($user->visitations_max_tanggal_visitation));
                })
                ->addColumn('tanggal_daftar_display', function ($user) {
                    if (empty($user->created_at)) {
                        return '-';
                    }

                    return Carbon::parse($user->created_at)->format('Y-m-d H:i');
                })
                ->addColumn('actions', function ($user) {
                    $tanggalLahir = $user->tanggal_lahir ?? '';

                    return '
                <div class="btn-group action-button-group w-100" role="group">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-calendar-plus mr-1"></i> Daftarkan
                            </button>
                            <div class="dropdown-menu dropdown-menu-right w-100">
                                <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="konsultasi" data-id="' . $user->id . '" data-nama="' . e($user->nama) . '"><i class="fas fa-stethoscope mr-2"></i>Konsultasi</a>
                                <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="lab" data-id="' . $user->id . '" data-nama="' . e($user->nama) . '"><i class="fas fa-flask mr-2"></i>Laboratorium</a>
                                <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="produk" data-id="' . $user->id . '" data-nama="' . e($user->nama) . '"><i class="fas fa-shopping-bag mr-2"></i>Produk dan Obat</a>
                                <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="event" data-id="' . $user->id . '" data-nama="' . e($user->nama) . '"><i class="fas fa-calendar-alt mr-2"></i>Event</a>
                                <a href="#" class="dropdown-item btn-daftarkan-pasien-rawatjalan" data-jenis="marketplace" data-id="' . $user->id . '" data-nama="' . e($user->nama) . '"><i class="fas fa-store mr-2"></i>Marketplace</a>
                            </div>
                        </div>
                        <a href="javascript:void(0);" 
                            class="btn btn-sm btn-info btn-info-pasien" 
                            data-id="' . $user->id . '">
                            <i class="fas fa-info-circle mr-1"></i> Info
                        </a>
                        <span class="ic-action"><button type="button" class="btn btn-sm btn-outline-primary btn-open-ic"
                               title="Isi IC Pendaftaran"
                               data-id="' . $user->id . '"
                               data-nama="' . e($user->nama) . '"
                               data-identity-label="' . e($user->identity_label ?? 'Identitas') . '"
                               data-identity-number="' . e($user->identity_number ?? $user->nik ?? '') . '"
                               data-alamat="' . e($user->alamat ?? '') . '"
                               data-nohp="' . e($user->no_hp ?? '') . '"
                               data-tgllahir="' . e($tanggalLahir) . '">
                               <i class="fas fa-file-signature mr-1"></i> Isi IC
                             </button></span>
                </div>';
                })
                ->rawColumns(['status_pasien_icon', 'status_akses', 'status_review', 'merchandise', 'referral_display', 'actions'])
                ->make(true);
        }

        $metodeBayar = MetodeBayar::all();
        $dokters = Dokter::with('spesialisasi')->get();
        $kliniks = Klinik::all();
        $stats = $this->getPatientIndexStats($startDate, $endDate);
        $defaultStartDate = $startDate->toDateString();
        $defaultEndDate = $endDate->toDateString();

        $pasienName = '';

        return view('erm.pasiens.index', compact('metodeBayar', 'dokters', 'pasienName', 'kliniks', 'stats', 'defaultStartDate', 'defaultEndDate'));
    }

    public function create(Request $request)
    {
    $metodeBayar = MetodeBayar::all();
    $dokters = Dokter::with(['spesialisasi', 'user'])->get();
    $kliniks = Klinik::all();
    $provinces = Province::all();
    $employees = Employee::active()->orderBy('nama')->get(['id', 'nama', 'no_induk']);
    $events = MarketingEvent::query()
        ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
        ->orderByDesc('tanggal_mulai')
        ->orderBy('nama_event')
        ->get(['id', 'kode_event', 'nama_event', 'status']);
    
    // Check if we're editing an existing patient
    $pasien = null;
    $isEditing = false;
    
        if ($request->has('edit_id')) {
            // eager-load nested area relations so the view can access province/regency/district
            $pasien = Pasien::with(['village.district.regency.province', 'referralable'])->find($request->edit_id);
            $isEditing = true;
        }
    
    return view('erm.pasiens.create', compact(
        'metodeBayar', 
        'dokters', 
        'provinces', 
        'employees',
        'events',
        'kliniks', 
        'pasien', 
        'isEditing'
    ));
}

    public function store(Request $request)
{
    $request->merge([
        'identity_document' => $request->input('identity_document', 'ktp'),
        'identity_number' => $request->input('identity_number', $request->input('nik')),
        'referral_type' => $request->input('referral_type', Pasien::REFERRAL_TYPE_WALK_IN),
        'no_hp' => $this->normalizePhoneNumber($request->input('no_hp')),
        'no_hp2' => $this->normalizePhoneNumber($request->input('no_hp2')),
    ]);

    $validator = Validator::make($request->all(), [
        'identity_document' => 'required|in:ktp,sim,paspor,kia',
        'identity_number' => [
            'required',
            'string',
            'max:50',
            Rule::unique('erm_pasiens', 'identity_number')->ignore($request->pasien_id, 'id'),
        ],
        'referral_type' => 'required|in:walk_in,pasien,dokter,employee,social_media,marketplace,event,website,partnership,google_maps',
        'referral_target_pasien_id' => 'nullable|string|exists:erm_pasiens,id',
        'referral_employee_id' => 'nullable|integer|exists:hrd_employee,id',
        'referral_dokter_id' => 'nullable|integer|exists:erm_dokters,id',
        'referral_event_id' => 'nullable|integer|exists:marketing_event,id',
        'referral_detail' => 'nullable|string|max:255',
        'is_employee_patient' => 'nullable|in:0,1',
        'duplicate_name_birthdate_acknowledged' => 'nullable|in:0,1',
        'employee_id' => 'nullable|integer|exists:hrd_employee,id',
        'nama' => 'required|string|max:255',
        'tanggal_lahir' => 'required|date',
        'gender' => 'required|in:Laki-laki,Perempuan',
        'agama' => 'nullable',
        'marital_status' => 'nullable',
        'pendidikan' => 'nullable',
        'pekerjaan' => 'nullable',
        'gol_darah' => 'nullable',
        'alamat' => 'required',
        'province' => 'required',
        'regency' => 'required',
        'district' => 'required',
        'village' => 'required',
        'no_hp' => 'required|string|max:15',
        'email' => 'nullable|email',
        'instagram' => 'nullable|string|max:255',
        'status_pasien' => 'nullable|in:Regular,VIP,Familia,Black Card',
        'status_akses' => 'nullable|in:normal,akses cepat',
    ]);

    $validator->after(function ($validator) use ($request) {
        if ($request->identity_document === 'ktp') {
            $identityNumber = (string) $request->identity_number;

            if (!preg_match('/^\d{16}$/', $identityNumber)) {
                $validator->errors()->add('identity_number', 'Nomor identitas untuk KTP harus 16 digit angka.');
            }
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_PASIEN && empty($request->referral_target_pasien_id)) {
            $validator->errors()->add('referral_target_pasien_id', 'Silakan pilih pasien sumber referral.');
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_EMPLOYEE && empty($request->referral_employee_id)) {
            $validator->errors()->add('referral_employee_id', 'Silakan pilih karyawan sumber referral.');
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_DOKTER && empty($request->referral_dokter_id)) {
            $validator->errors()->add('referral_dokter_id', 'Silakan pilih dokter sumber referral.');
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_EVENT && empty($request->referral_event_id)) {
            $validator->errors()->add('referral_event_id', 'Silakan pilih event sumber referral.');
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_MARKETPLACE && !in_array(strtolower(trim((string) $request->referral_detail)), Pasien::marketplaceReferralOptions(), true)) {
            $validator->errors()->add('referral_detail', 'Silakan pilih sumber marketplace yang valid.');
        }

        if ($request->referral_type === Pasien::REFERRAL_TYPE_SOCIAL_MEDIA && !in_array(strtolower(trim((string) $request->referral_detail)), Pasien::socialMediaReferralOptions(), true)) {
            $validator->errors()->add('referral_detail', 'Silakan pilih sumber social media yang valid.');
        }

        if (in_array($request->referral_type, [Pasien::REFERRAL_TYPE_PARTNERSHIP, Pasien::REFERRAL_TYPE_GOOGLE_MAPS], true) && empty(trim((string) $request->referral_detail))) {
            $validator->errors()->add('referral_detail', 'Silakan isi detail referral.');
        }

        if ($request->input('is_employee_patient') === '1' && empty($request->employee_id)) {
            $validator->errors()->add('employee_id', 'Silakan pilih employee untuk pasien karyawan.');
        }

        if (!$this->startsWith62($request->input('no_hp'))) {
            $validator->errors()->add('no_hp', 'No Telepon 1 harus diawali dengan 62.');
        }

        if (!empty($request->input('no_hp2')) && !$this->startsWith62($request->input('no_hp2'))) {
            $validator->errors()->add('no_hp2', 'No Telepon Darurat harus diawali dengan 62.');
        }

        if (!empty($request->employee_id) && $this->employeeAlreadyLinkedToAnotherPatient($request->employee_id, $request->pasien_id)) {
            $validator->errors()->add('employee_id', 'Employee tersebut sudah terhubung ke pasien lain. Satu employee hanya boleh menggunakan satu pasien.');
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $duplicateNameBirthdatePatients = $this->findPatientsWithSameNameAndBirthdate(
        $request->nama,
        $request->tanggal_lahir,
        $request->pasien_id
    );

    if ($duplicateNameBirthdatePatients->isNotEmpty() && $request->input('duplicate_name_birthdate_acknowledged') !== '1') {
        return response()->json([
            'status' => 'duplicate_name_birthdate',
            'message' => 'Terdapat pasien dengan kombinasi nama dan tanggal lahir yang sama.',
            'duplicate_name_birthdate' => true,
            'count' => $duplicateNameBirthdatePatients->count(),
            'patients' => $duplicateNameBirthdatePatients->values(),
        ], 422);
    }

    $userId = Auth::id();
    $referralType = $request->filled('referral_type') ? $request->referral_type : Pasien::REFERRAL_TYPE_WALK_IN;
    $referralPasienId = $referralType === Pasien::REFERRAL_TYPE_PASIEN ? $request->input('referral_target_pasien_id') : null;
    $referralEmployeeId = $referralType === Pasien::REFERRAL_TYPE_EMPLOYEE ? $request->input('referral_employee_id') : null;
    $referralDokterId = $referralType === Pasien::REFERRAL_TYPE_DOKTER ? $request->input('referral_dokter_id') : null;
    $referralEventId = $referralType === Pasien::REFERRAL_TYPE_EVENT ? $request->input('referral_event_id') : null;
    $selectedEvent = null;

    if ($referralEventId) {
        $selectedEvent = MarketingEvent::query()->select(['id', 'kode_event'])->find($referralEventId);
    }

    $referralableId = null;
    if ($referralType === Pasien::REFERRAL_TYPE_EMPLOYEE && $referralEmployeeId) {
        $referralableId = (string) $referralEmployeeId;
    } elseif ($referralType === Pasien::REFERRAL_TYPE_DOKTER && $referralDokterId) {
        $referralableId = (string) $referralDokterId;
    } elseif ($referralType === Pasien::REFERRAL_TYPE_EVENT && $selectedEvent) {
        $referralableId = (string) $selectedEvent->id;
    }

    $referralDetail = null;
    if ($referralType === Pasien::REFERRAL_TYPE_EVENT && $selectedEvent) {
        $referralDetail = trim((string) $selectedEvent->kode_event);
    } elseif (in_array($referralType, [Pasien::REFERRAL_TYPE_MARKETPLACE, Pasien::REFERRAL_TYPE_SOCIAL_MEDIA, Pasien::REFERRAL_TYPE_PARTNERSHIP, Pasien::REFERRAL_TYPE_GOOGLE_MAPS], true)) {
        $referralDetail = strtolower(trim((string) $request->referral_detail));
    }
    $referralAttributes = Pasien::buildReferralAttributes(
        $referralType,
        $referralPasienId,
        $referralDetail,
        null,
        $referralableId
    );

    DB::beginTransaction();

    try {
        $pasienId = $request->pasien_id;
        
        // Check if we're updating or creating
        if (!empty($pasienId)) {
            // Update existing patient
            $pasien = Pasien::findOrFail($pasienId);
            $pasien->update([
                'identity_document' => $request->identity_document,
                'identity_number' => $request->identity_number,
                'referral_type' => $referralType,
                'referral_detail' => $referralAttributes['referral_detail'],
                'referralable_type' => $referralAttributes['referralable_type'],
                'referralable_id' => $referralAttributes['referralable_id'],
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
                'status_pasien' => $request->filled('status_pasien') ? $request->status_pasien : ($pasien->status_pasien ?? 'Regular'),
                'status_akses' => $request->status_akses ?? 'normal',
                'user_id' => $userId,
                'employee_id' => $request->employee_id,
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
                'identity_document' => $request->identity_document,
                'identity_number' => $request->identity_number,
                'referral_type' => $referralType,
                'referral_detail' => $referralAttributes['referral_detail'],
                'referralable_type' => $referralAttributes['referralable_type'],
                'referralable_id' => $referralAttributes['referralable_id'],
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
                'employee_id' => $request->employee_id,
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
        // eager-load full area hierarchy so AJAX consumers can display names
        $pasien = Pasien::with(['village.district.regency.province', 'employee', 'referralable'])->findOrFail($id);

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

    public function update(Request $request, $id)
    {
        $request->merge([
            'identity_document' => $request->input('identity_document', 'ktp'),
            'identity_number' => $request->input('identity_number', $request->input('nik')),
            'no_hp' => $this->normalizePhoneNumber($request->input('no_hp')),
            'no_hp2' => $this->normalizePhoneNumber($request->input('no_hp2')),
        ]);

        $validator = Validator::make($request->all(), [
            'identity_document' => 'required|in:ktp,sim,paspor,kia',
            'identity_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('erm_pasiens', 'identity_number')->ignore($id, 'id'),
            ],
            'employee_id' => 'nullable|integer|exists:hrd_employee,id',
            'nama' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'alamat' => 'required|string',
            'province' => 'required',
            'regency' => 'required',
            'district' => 'required',
            'village' => 'required',
            'no_hp' => 'required|string|max:15',
            'status_pasien' => 'nullable|in:Regular,VIP,Familia,Black Card,Red Flag',
            'status_akses' => 'nullable|in:normal,akses cepat',
            'status_review' => 'nullable|in:sudah,belum',
        ]);

        $validator->after(function ($validator) use ($request, $id) {
            if ($request->identity_document === 'ktp') {
                $identityNumber = (string) $request->identity_number;

                if (!preg_match('/^\d{16}$/', $identityNumber)) {
                    $validator->errors()->add('identity_number', 'Nomor identitas untuk KTP harus 16 digit angka.');
                }
            }

            if (!$this->startsWith62($request->input('no_hp'))) {
                $validator->errors()->add('no_hp', 'No Telepon 1 harus diawali dengan 62.');
            }

            if (!empty($request->input('no_hp2')) && !$this->startsWith62($request->input('no_hp2'))) {
                $validator->errors()->add('no_hp2', 'No Telepon Darurat harus diawali dengan 62.');
            }

            if (!empty($request->employee_id) && $this->employeeAlreadyLinkedToAnotherPatient($request->employee_id, $id)) {
                $validator->errors()->add('employee_id', 'Employee tersebut sudah terhubung ke pasien lain. Satu employee hanya boleh menggunakan satu pasien.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pasien = Pasien::findOrFail($id);
            $pasien->update([
                'identity_document' => $request->identity_document,
                'identity_number' => $request->identity_number,
                'nama' => $request->nama,
                'tanggal_lahir' => $request->tanggal_lahir,
                'gender' => $request->gender,
                'alamat' => $request->alamat,
                'no_hp' => $request->no_hp,
                'status_pasien' => $request->status_pasien ?? 'Regular',
                'status_akses' => $request->status_akses ?? 'normal',
                'status_review' => $request->status_review ?? 'belum',
                'user_id' => Auth::id(),
                'employee_id' => $request->employee_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data pasien berhasil diperbarui.',
                'data' => [
                    'id' => $pasien->id,
                    'nama' => $pasien->nama,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data pasien',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'status_pasien' => 'required|in:Regular,VIP,Familia,Black Card,Red Flag'
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
            'status_pasien' => 'required|in:Regular,VIP,Familia,Black Card,Red Flag',
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

    public function updateStatusReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_review' => 'required|in:sudah,belum'
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
            $pasien->status_review = $request->status_review;
            $pasien->save();

            return response()->json([
                'success' => true,
                'message' => 'Status review pasien berhasil diperbarui',
                'data' => [
                    'id' => $pasien->id,
                    'status_review' => $pasien->status_review
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status review pasien'
            ], 500);
        }
    }

    private function employeeAlreadyLinkedToAnotherPatient($employeeId, $exceptPasienId = null): bool
    {
        return Pasien::query()
            ->where('employee_id', $employeeId)
            ->when($exceptPasienId, function ($query) use ($exceptPasienId) {
                $query->where('id', '!=', $exceptPasienId);
            })
            ->exists();
    }

    private function normalizePhoneNumber($phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return substr($digits, 0, 15);
        }

        if (str_starts_with($digits, '0')) {
            return substr('62' . substr($digits, 1), 0, 15);
        }

        return substr('62' . $digits, 0, 15);
    }

    private function startsWith62($phoneNumber): bool
    {
        return is_string($phoneNumber) && str_starts_with($phoneNumber, '62');
    }

    private function resolveIndexDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function getPatientIndexStats(Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = Pasien::query()
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString());

        $statusCounts = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(status_pasien, ''), 'Regular') as stat_key, COUNT(*) as total")
            ->groupBy('stat_key')
            ->pluck('total', 'stat_key');

        $referralCounts = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(referral_type, ''), 'walk_in') as stat_key, COUNT(*) as total")
            ->groupBy('stat_key')
            ->pluck('total', 'stat_key');

        $statusDefinitions = [
            'Regular' => ['label' => 'Regular', 'icon' => 'fas fa-user', 'theme' => 'primary'],
            'VIP' => ['label' => 'VIP', 'icon' => 'fas fa-crown', 'theme' => 'warning'],
            'Familia' => ['label' => 'Familia', 'icon' => 'fas fa-users', 'theme' => 'success'],
            'Black Card' => ['label' => 'Black Card', 'icon' => 'fas fa-credit-card', 'theme' => 'dark'],
            'Red Flag' => ['label' => 'Red Flag', 'icon' => 'fas fa-exclamation-triangle', 'theme' => 'danger'],
        ];

        $referralDefinitions = [
            Pasien::REFERRAL_TYPE_WALK_IN => ['label' => 'Walk-in', 'icon' => 'fas fa-walking', 'theme' => 'primary'],
            Pasien::REFERRAL_TYPE_PASIEN => ['label' => 'Pasien', 'icon' => 'fas fa-user-friends', 'theme' => 'info'],
            Pasien::REFERRAL_TYPE_DOKTER => ['label' => 'Dokter', 'icon' => 'fas fa-user-md', 'theme' => 'success'],
            Pasien::REFERRAL_TYPE_EMPLOYEE => ['label' => 'Karyawan', 'icon' => 'fas fa-id-badge', 'theme' => 'teal'],
            Pasien::REFERRAL_TYPE_SOCIAL_MEDIA => ['label' => 'Social Media', 'icon' => 'fas fa-hashtag', 'theme' => 'rose'],
            Pasien::REFERRAL_TYPE_MARKETPLACE => ['label' => 'Marketplace', 'icon' => 'fas fa-store', 'theme' => 'orange'],
            Pasien::REFERRAL_TYPE_EVENT => ['label' => 'Event', 'icon' => 'fas fa-calendar-alt', 'theme' => 'purple'],
            Pasien::REFERRAL_TYPE_WEBSITE => ['label' => 'Website', 'icon' => 'fas fa-globe', 'theme' => 'cyan'],
            Pasien::REFERRAL_TYPE_PARTNERSHIP => ['label' => 'Partnership', 'icon' => 'fas fa-handshake', 'theme' => 'slate'],
            Pasien::REFERRAL_TYPE_GOOGLE_MAPS => ['label' => 'Google Maps', 'icon' => 'fas fa-map-marker-alt', 'theme' => 'danger'],
        ];

        $statuses = [];
        foreach ($statusDefinitions as $key => $definition) {
            $statuses[$key] = $definition + [
                'count' => (int) ($statusCounts[$key] ?? 0),
            ];
        }

        $referrals = [];
        foreach ($referralDefinitions as $key => $definition) {
            $referrals[$key] = $definition + [
                'count' => (int) ($referralCounts[$key] ?? 0),
            ];
        }

        return [
            'total_new' => [
                'label' => 'Pasien Baru',
                'icon' => 'fas fa-user-plus',
                'theme' => 'primary',
                'count' => (clone $baseQuery)->count(),
            ],
            'statuses' => $statuses,
            'referrals' => $referrals,
            'range' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    private function formatReferralDisplay(Pasien $pasien): string
    {
        $typeLabel = match ($pasien->referral_type) {
            Pasien::REFERRAL_TYPE_WALK_IN => 'Walk-in',
            Pasien::REFERRAL_TYPE_PASIEN => 'Pasien',
            Pasien::REFERRAL_TYPE_DOKTER => 'Dokter',
            Pasien::REFERRAL_TYPE_EMPLOYEE => 'Karyawan',
            Pasien::REFERRAL_TYPE_SOCIAL_MEDIA => 'Social Media',
            Pasien::REFERRAL_TYPE_MARKETPLACE => 'Marketplace',
            Pasien::REFERRAL_TYPE_EVENT => 'Event',
            Pasien::REFERRAL_TYPE_WEBSITE => 'Website',
            Pasien::REFERRAL_TYPE_PARTNERSHIP => 'Partnership',
            Pasien::REFERRAL_TYPE_GOOGLE_MAPS => 'Google Maps',
            default => 'Walk-in',
        };

        $iconClass = match ($pasien->referral_type) {
            Pasien::REFERRAL_TYPE_WALK_IN => 'fas fa-walking',
            Pasien::REFERRAL_TYPE_PASIEN => 'fas fa-user-friends',
            Pasien::REFERRAL_TYPE_DOKTER => 'fas fa-user-md',
            Pasien::REFERRAL_TYPE_EMPLOYEE => 'fas fa-id-badge',
            Pasien::REFERRAL_TYPE_SOCIAL_MEDIA => 'fas fa-hashtag',
            Pasien::REFERRAL_TYPE_MARKETPLACE => 'fas fa-store',
            Pasien::REFERRAL_TYPE_EVENT => 'fas fa-calendar-alt',
            Pasien::REFERRAL_TYPE_WEBSITE => 'fas fa-globe',
            Pasien::REFERRAL_TYPE_PARTNERSHIP => 'fas fa-handshake',
            Pasien::REFERRAL_TYPE_GOOGLE_MAPS => 'fas fa-map-marker-alt',
            default => 'fas fa-walking',
        };

        $detail = null;

        if ($pasien->referral_type === Pasien::REFERRAL_TYPE_PASIEN && $pasien->referralable) {
            $detail = $pasien->referralable->nama . ' (RM: ' . $pasien->referralable->id . ')';
        } elseif ($pasien->referral_type === Pasien::REFERRAL_TYPE_EMPLOYEE && $pasien->referralable) {
            $detail = $pasien->referralable->nama;
        } elseif ($pasien->referral_type === Pasien::REFERRAL_TYPE_DOKTER && $pasien->referralable) {
            $detail = $pasien->referralable->user->name ?? ('Dokter ID ' . $pasien->referralable->id);
        } elseif ($pasien->referral_type === Pasien::REFERRAL_TYPE_EVENT && $pasien->referralable) {
            $detail = $pasien->referralable->nama_event ?? $pasien->referral_detail;
        } elseif (!empty($pasien->referral_detail)) {
            $detail = ucwords(str_replace('_', ' ', (string) $pasien->referral_detail));
        }

        $label = $detail ? $typeLabel . ': ' . $detail : $typeLabel;

        return '<span class="d-inline-flex align-items-center">'
            . '<i class="' . e($iconClass) . ' mr-2"></i>'
            . '<span>' . e($label) . '</span>'
            . '</span>';
    }

    private function formatStatusPasienIcon(?string $statusPasien): string
    {
        $statusConfig = [
            'VIP' => ['color' => '#FFD700', 'icon' => 'fas fa-crown', 'title' => 'VIP Member'],
            'Familia' => ['color' => '#32CD32', 'icon' => 'fas fa-users', 'title' => 'Familia Member'],
            'Black Card' => ['color' => '#2F2F2F', 'icon' => 'fas fa-credit-card', 'title' => 'Black Card Member'],
            'Red Flag' => ['color' => '#FF0000', 'icon' => 'fas fa-exclamation-triangle', 'title' => 'Red Flag'],
        ];

        $status = $statusPasien ?? 'Regular';

        if (!isset($statusConfig[$status])) {
            return '';
        }

        $config = $statusConfig[$status];

        return '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center ml-2" '
            . 'style="width: 20px; height: 20px; background-color: ' . $config['color'] . '; border-radius: 50%;" '
            . 'title="' . e($config['title']) . '">'
            . '<i class="' . e($config['icon']) . ' text-white" style="font-size: 11px;"></i>'
            . '</span>';
    }

    private function formatIndonesianDate(Carbon $date): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $date->day . ' ' . $months[(int) $date->month] . ' ' . $date->year;
    }

    private function findPatientsWithSameNameAndBirthdate($nama, $tanggalLahir, $exceptPasienId = null)
    {
        return Pasien::query()
            ->select(['id', 'nama', 'tanggal_lahir', 'alamat'])
            ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower(trim((string) $nama))])
            ->whereDate('tanggal_lahir', $tanggalLahir)
            ->when($exceptPasienId, function ($query) use ($exceptPasienId) {
                $query->where('id', '!=', $exceptPasienId);
            })
            ->orderBy('id')
            ->get()
            ->map(function ($pasien) {
                return [
                    'id' => (string) $pasien->id,
                    'nama' => $pasien->nama,
                    'tanggal_lahir' => !empty($pasien->tanggal_lahir) ? date('Y-m-d', strtotime((string) $pasien->tanggal_lahir)) : null,
                    'alamat' => $pasien->alamat,
                ];
            });
    }
}
