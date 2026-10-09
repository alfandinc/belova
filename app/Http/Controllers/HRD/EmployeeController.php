<?php

namespace App\Http\Controllers\HRD;

use App\Exports\EmployeeExport;
use App\Http\Controllers\Controller;
use App\Models\HRD\Division;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeLog;
use App\Models\HRD\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    private const DOCUMENTS = ['doc_cv', 'doc_ktp', 'doc_pendukung']; // contract documents live on the contracts

    /** Hrd / Admin change karyawan data (see routes); Ceo / Head Manager only view it. */
    public static function canManage(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['Hrd', 'Admin']);
    }

    /**
     * Search employees for select2 (sales field)
     */
    public function searchForSelect2(Request $request)
    {
        $search = $request->input('q'); // select2 uses 'q' for the search term
        $query = Employee::active();
        if ($search) {
            $query->where('nama', 'like', "%$search%");
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama']);
        return response()->json($results);
    }

    /**
     * Karyawan list query with the list filters (status, divisi, perusahaan and the quick filters of the
     * summary chips). Shared by the table and the Excel export.
     */
    private function filteredQuery(Request $request): Builder
    {
        $employees = Employee::withInactive()->with(['user', 'positions.divisions'])->select('hrd_employee.*');

        $statusFilter = $request->input('status_filter');
        if ($statusFilter === 'active') {
            $employees->whereRaw('LOWER(status) <> ?', ['tidak aktif']);
        } elseif ($statusFilter === 'inactive') {
            $employees->whereRaw('LOWER(status) = ?', ['tidak aktif']);
            // Turnover: why / which year they left
            if ($request->filled('nonaktif_alasan')) {
                $employees->where('nonaktif_alasan', $request->input('nonaktif_alasan'));
            }
            if ($request->filled('nonaktif_tahun')) {
                $employees->whereYear('nonaktif_tanggal', (int) $request->input('nonaktif_tahun'));
            }
        } elseif (in_array($statusFilter, ['tetap', 'kontrak', 'freelance'], true)) {
            $employees->where('status', $statusFilter);
        }

        // Employees are linked to divisions via positions
        if ($request->filled('division_id')) {
            $divisionId = $request->input('division_id');
            $employees->whereHas('positions.divisions', fn($q) => $q->where('hrd_division.id', $divisionId));
        }
        if ($request->filled('position_id')) {
            $employees->whereHas('positions', fn($q) => $q->where('hrd_position.id', $request->input('position_id')));
        }
        if ($request->filled('perusahaan')) {
            // Main company or one of the other companies
            $perusahaan = $request->input('perusahaan');
            $employees->where(fn($q) => $q->where('perusahaan', $perusahaan)->orWhereJsonContains('perusahaan_list', $perusahaan));
        }

        $this->applyQuickFilter($employees, $request->input('quick'));

        return $employees;
    }

    /** The "perlu tindak lanjut" chips. */
    private function applyQuickFilter(Builder $employees, ?string $quick): void
    {
        switch ($quick) {
            case 'kontrak_segera':
                $employees->where('status', 'kontrak')->whereBetween('kontrak_berakhir', [today(), today()->addDays(30)]);
                break;
            case 'kontrak_lewat':
                $employees->where('status', 'kontrak')->where('kontrak_berakhir', '<', today());
                break;
            case 'kontrak_kosong':
                $employees->where('status', 'kontrak')->whereNull('kontrak_berakhir');
                break;
            case 'belum_lengkap':
                $employees->belumLengkap();
                break;
            case 'baru': // masa percobaan
                $employees->where('tanggal_masuk', '>=', today()->subMonths(3));
                break;
            case 'ultah':
                $employees->whereMonth('tanggal_lahir', today()->month);
                break;
            case 'tanpa_akun':
                $employees->whereNull('hrd_employee.user_id');
                break;
            case 'tanpa_finger':
                $employees->where(fn($q) => $q->whereNull('finger_id')->orWhereRaw("TRIM(finger_id) = ''"));
                break;
        }
    }

    public const QUICK_FILTERS = [
        'kontrak_segera', 'kontrak_lewat', 'kontrak_kosong', 'belum_lengkap', 'baru', 'ultah', 'tanpa_akun', 'tanpa_finger',
    ];

    /** Counts for the summary chips above the table (active employees, independent of the filters). */
    private function summary(): array
    {
        $counts = [];
        foreach (self::QUICK_FILTERS as $quick) {
            $query = Employee::query();
            $this->applyQuickFilter($query, $quick);
            $counts[$quick] = $query->count();
        }

        return $counts;
    }

    public function index(Request $request)
    {
        if (!$request->ajax()) {
            $divisions = Division::where('is_active', true)->orderBy('name')->get();
            $positions = Position::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $canManage = self::canManage(); // Ceo / Head Manager only view

            return view('hrd.employee.index', compact('divisions', 'positions', 'canManage'));
        }

        $dataTable = DataTables::of($this->filteredQuery($request))
            ->addColumn('position', function ($employee) {
                $names = $employee->positions->pluck('name')->toArray();
                return empty($names) ? '-' : implode(', ', $names);
            })
            ->addColumn('division', function ($employee) {
                $divs = $employee->positions->flatMap(fn($position) => $position->divisions->pluck('name'))
                    ->unique()->filter()->values()->all();
                return empty($divs) ? '-' : implode(', ', $divs);
            })
            ->addColumn('belum_lengkap', fn($employee) => $employee->dataBelumLengkap())
            ->addColumn('nonaktif_label', function ($employee) {
                if ($employee->status !== 'tidak aktif' || !$employee->nonaktif_alasan) {
                    return null;
                }
                return (Employee::NONAKTIF_ALASAN[$employee->nonaktif_alasan] ?? $employee->nonaktif_alasan)
                    . ($employee->nonaktif_tanggal ? ' · ' . $employee->nonaktif_tanggal->format('d/m/Y') : '');
            })
            ->addColumn('masa_kerja', fn($employee) => $employee->masaKerja())
            ->addColumn('perusahaan_daftar', fn($employee) => array_map(fn($nama) => [
                'nama' => $nama,
                'singkat' => Employee::PERUSAHAAN_SINGKAT[$nama] ?? $nama,
                'utama' => $nama === $employee->perusahaan,
            ], $employee->daftarPerusahaan()))
            ->addColumn('photo_url', fn($employee) => $employee->photo ? asset('storage/' . $employee->photo) : null)
            ->addColumn('action', '')
            ->with('summary', $this->summary());

        $dataTable->order(function ($query) use ($request) {
            $colIndex = (int) $request->input('order.0.column');
            $direction = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
            $colName = $request->input("columns.$colIndex.name");

            // Only known columns (no raw input in SQL)
            $orders = [
                'hrd_employee.nik' => "hrd_employee.nik $direction",
                // Numeric no induk first, by number, then the rest as text
                'hrd_employee.no_induk' => "(hrd_employee.no_induk REGEXP '^[0-9]+$') DESC, CAST(hrd_employee.no_induk AS UNSIGNED) $direction, hrd_employee.no_induk $direction",
                'hrd_employee.nama' => "hrd_employee.nama $direction",
                'hrd_employee.perusahaan' => "hrd_employee.perusahaan IS NULL, hrd_employee.perusahaan $direction, hrd_employee.nama",
                'hrd_employee.tanggal_lahir' => "hrd_employee.tanggal_lahir IS NULL, hrd_employee.tanggal_lahir $direction",
                'hrd_employee.tanggal_masuk' => "hrd_employee.tanggal_masuk IS NULL, hrd_employee.tanggal_masuk $direction",
                'hrd_employee.status' => "hrd_employee.status $direction, hrd_employee.nama",
                // Sisa kontrak: kontrak employees first, by end date
                'hrd_employee.kontrak_berakhir' => "hrd_employee.status <> 'kontrak', hrd_employee.kontrak_berakhir IS NULL, hrd_employee.kontrak_berakhir $direction",
            ];
            $query->orderByRaw($orders[$colName] ?? 'hrd_employee.nama asc');
        });

        return $dataTable->make(true);
    }

    /** Excel of the list with the current filters (no salary data). */
    public function export(Request $request)
    {
        $employees = $this->filteredQuery($request)
            // Same text as the table's search box
            ->when(trim((string) $request->input('search.value')), fn($q, $s) => $q->where(fn($w) => $w
                ->where('hrd_employee.nama', 'like', "%$s%")
                ->orWhere('hrd_employee.nik', 'like', "%$s%")
                ->orWhere('hrd_employee.no_induk', 'like', "%$s%")))
            ->orderBy('hrd_employee.nama')->get();

        return Excel::download(new EmployeeExport($employees), 'data-karyawan-' . now()->format('Ymd') . '.xlsx');
    }

    /**
     * The add / edit form is a modal on the karyawan list: AJAX requests get the form html,
     * a direct visit opens the list with the modal.
     */
    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('hrd.employee.index', ['create' => 1]);
        }

        return response()->json([
            'success' => true,
            'html' => view('hrd.employee._form', $this->formData() + ['nextNoInduk' => $this->generateNoInduk()])->render(),
        ]);
    }

    public function edit(Request $request, $id)
    {
        if (!$request->ajax()) {
            return redirect()->route('hrd.employee.index', ['edit' => $id]);
        }

        $employee = Employee::withInactive()->with(['positions', 'user'])->findOrFail($id);
        $primaryPositionId = $employee->positions->firstWhere('pivot.is_primary', 1)->id ?? null;

        return response()->json([
            'success' => true,
            'html' => view('hrd.employee._form', $this->formData($employee) + compact('employee', 'primaryPositionId'))->render(),
        ]);
    }

    private function formData(?Employee $employee = null): array
    {
        // Keep the employee's current positions selectable even if they were deactivated, otherwise saving drops them
        $currentIds = $employee ? $employee->positions->pluck('id')->all() : [];
        $positions = Position::where('is_active', true)->orWhereIn('id', $currentIds)->orderBy('name')->get();

        // Jurusan suggestions: common ones + what is already in use
        $jurusanList = Employee::withInactive()->whereNotNull('pendidikan')->distinct()->pluck('pendidikan')
            ->map(fn($p) => Employee::splitPendidikan($p)[1])
            ->merge(['Keperawatan', 'Kebidanan', 'Farmasi', 'Kedokteran', 'Kedokteran Gigi', 'Kesehatan Masyarakat', 'Gizi',
                'Analis Kesehatan', 'Rekam Medis', 'Akuntansi', 'Manajemen', 'Ilmu Komunikasi', 'Psikologi', 'Hukum',
                'Teknik Informatika', 'Sistem Informasi', 'Administrasi Bisnis', 'Perhotelan', 'Desain Komunikasi Visual'])
            ->filter(fn($j) => mb_strlen($j) >= 3)->unique(fn($j) => mb_strtolower($j))->sort()->values();

        return [
            'jurusanList' => $jurusanList,
            'positions' => $positions,
            'gajiPokokList' => \App\Models\HRD\PrMasterGajipokok::all(),
            'tunjanganJabatanList' => \App\Models\HRD\PrMasterTunjanganJabatan::all(),
        ];
    }

    /** Login accounts for the form's "Akun login" select: users not linked to another employee. */
    public function searchUsers(Request $request)
    {
        $exceptEmployee = $request->integer('employee_id');
        $linked = Employee::withInactive()->whereNotNull('user_id')
            ->when($exceptEmployee, fn($q) => $q->where('id', '<>', $exceptEmployee))
            ->pluck('user_id');

        $users = User::with('roles:id,name')
            ->whereNotIn('id', $linked)
            ->when($request->input('q'), fn($q, $s) => $q->where(fn($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))
            ->orderBy('name')->limit(20)->get(['id', 'name', 'email']);

        return response()->json(['results' => $users->map(fn($u) => [
            'id' => $u->id,
            'text' => $u->name . ' — ' . $u->email . ($u->roles->isNotEmpty() ? ' (' . $u->roles->pluck('name')->implode(', ') . ')' : ''),
        ])]);
    }

    /** Next free `no_induk` in YYMMXXX format (after the highest one, so deleted / edited numbers never collide). */
    private function generateNoInduk(): string
    {
        $prefix = date('y') . date('m');
        $last = Employee::withInactive()
            ->where('no_induk', 'like', $prefix . '%')
            ->whereRaw('LENGTH(no_induk) = 7')
            ->max('no_induk');
        $nextSeq = $last ? ((int) substr($last, 4)) + 1 : 1;

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }

    private function rules(?Employee $employee = null): array
    {
        $ignore = $employee->id ?? null;

        return [
            'nama' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            // Required: cuti / slip gaji / divisi depend on these
            'jenis_kelamin' => 'required|in:L,P,l,p',
            'tanggal_masuk' => 'required|date',
            // One or more companies; `perusahaan` = the main one (defaults to the first)
            'perusahaan_list' => 'required|array|min:1',
            'perusahaan_list.*' => [Rule::in(Employee::PERUSAHAAN)],
            'perusahaan' => ['nullable', Rule::in(Employee::PERUSAHAAN)],
            'position_ids' => 'required|array|min:1',
            'position_ids.*' => 'exists:hrd_position,id',
            'primary_position' => 'nullable|exists:hrd_position,id',
            'nik' => ['nullable', 'string', Rule::unique('hrd_employee', 'nik')->ignore($ignore)],
            'no_induk' => ['nullable', 'string', Rule::unique('hrd_employee', 'no_induk')->ignore($ignore)],
            'no_darurat' => 'nullable|string|max:50',
            'darurat_nama' => 'nullable|string|max:100',
            'darurat_hubungan' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
            'gol_darah' => 'nullable|string|max:5',
            // Saved together as `pendidikan`, e.g. "S1 Ilmu Komunikasi"
            'pendidikan_jenjang' => ['nullable', Rule::in(Employee::PENDIDIKAN)],
            'pendidikan_jurusan' => 'nullable|string|max:80',
            'no_hp' => 'nullable|string|max:30',
            // Matches the attendance machine's user id: two employees with one id would share an attendance
            'finger_id' => ['nullable', 'string', 'max:20', Rule::unique('hrd_employee', 'finger_id')->ignore($ignore)],
            'photo' => 'nullable|image|max:2048',
            'npwp' => 'nullable|string|max:30',
            'no_bpjs_kesehatan' => 'nullable|string|max:30',
            'no_bpjs_ketenagakerjaan' => 'nullable|string|max:30',
            'bank_nama' => 'nullable|string|max:50',
            'bank_no_rekening' => 'nullable|string|max:40',
            'bank_atas_nama' => 'nullable|string|max:100',
            'status_pernikahan' => ['nullable', Rule::in(array_keys(Employee::STATUS_PERNIKAHAN))],
            'jumlah_tanggungan' => 'nullable|integer|min:0|max:20',
            // Required: a NULL status is hidden by Employee's 'active' scope (LOWER(NULL) <> ... is never true)
            'status' => 'required|in:tetap,kontrak,tidak aktif,freelance',
            'nonaktif_alasan' => ['required_if:status,tidak aktif', 'nullable', Rule::in(array_keys(Employee::NONAKTIF_ALASAN))],
            'nonaktif_tanggal' => 'required_if:status,tidak aktif|nullable|date',
            'nonaktif_keterangan' => 'nullable|string|max:1000',
            'doc_cv' => 'nullable|file|max:2048',
            'doc_ktp' => 'nullable|file|max:2048',
            'doc_pendukung' => 'nullable|file|max:2048',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('hrd_employee', 'email')->ignore($ignore)],
            'kategori_pegawai' => 'nullable|string|in:normal,khusus',
            'gol_gaji_pokok_id' => 'nullable|exists:pr_master_gajipokok,id',
            'gol_tunjangan_jabatan_id' => 'nullable|exists:pr_master_tunjangan_jabatan,id',
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('hrd_employee', 'user_id')->ignore($ignore)],
        ];
    }

    private function messages(): array
    {
        return [
            'position_ids.required' => 'Pilih minimal satu posisi.',
            'perusahaan_list.required' => 'Pilih minimal satu perusahaan.',
            'nonaktif_alasan.required_if' => 'Alasan nonaktif wajib diisi jika status Tidak Aktif.',
            'nonaktif_tanggal.required_if' => 'Tanggal nonaktif wajib diisi jika status Tidak Aktif.',
            'user_id.unique' => 'Akun login ini sudah dipakai karyawan lain.',
            'finger_id.unique' => 'Finger ID ini sudah dipakai karyawan lain.',
        ];
    }

    /** Validated input -> employee columns (files stored, nonaktif fields only when tidak aktif). */
    private function employeeData(Request $request, array $data, ?Employee $employee = null): array
    {
        // Same folder as the self-service profile photo
        $folders = array_fill_keys(self::DOCUMENTS, 'documents/employees') + ['photo' => 'employees/photos'];
        foreach ($folders as $field => $folder) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                if ($employee && $employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
                $data[$field] = $request->file($field)->store($folder, 'public');
            }
        }

        $data['jenis_kelamin'] = strtoupper($data['jenis_kelamin']);
        $data['pendidikan'] = Employee::joinPendidikan($data['pendidikan_jenjang'] ?? null, $data['pendidikan_jurusan'] ?? null);
        unset($data['pendidikan_jenjang'], $data['pendidikan_jurusan']);
        $companies = array_values(array_unique($data['perusahaan_list']));
        $main = in_array($data['perusahaan'] ?? null, $companies, true) ? $data['perusahaan'] : $companies[0];
        $data['perusahaan'] = $main;
        $data['perusahaan_list'] = array_values(array_unique(array_merge([$main], $companies))); // main first
        // Rekening usually in the employee's own name
        if (!empty($data['bank_no_rekening']) && empty($data['bank_atas_nama'])) {
            $data['bank_atas_nama'] = $data['nama'];
        }
        if (($data['status_pernikahan'] ?? null) === null) {
            $data['jumlah_tanggungan'] = null;
        }
        if ($data['status'] !== 'tidak aktif') {
            $data['nonaktif_tanggal'] = $data['nonaktif_alasan'] = $data['nonaktif_keterangan'] = null;
        }
        unset($data['position_ids'], $data['primary_position']);

        return $data;
    }

    /**
     * Sync the position pivot. When no primary is chosen (the field is hidden with a single position),
     * the first position becomes primary so `$employee->position` / `->division` keep working.
     */
    private function syncPositions(Employee $employee, array $positionIds, $primary): void
    {
        $positionIds = array_values(array_unique(array_map('intval', $positionIds)));
        if ($positionIds && !in_array((int) $primary, $positionIds, true)) {
            $primary = $positionIds[0];
        }
        $sync = [];
        foreach ($positionIds as $pid) {
            $sync[$pid] = ['is_primary' => $pid === (int) $primary ? 1 : 0];
        }
        $employee->positions()->sync($sync);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $employee = DB::transaction(function () use ($request, $data) {
            $employee = Employee::create($this->employeeData($request, $data));
            $this->syncPositions($employee, $data['position_ids'], $data['primary_position'] ?? null);
            EmployeeLog::note($employee->id, 'dibuat', null, null, 'Status: ' . ucfirst($employee->status));

            return $employee;
        });

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => ['id' => $employee->id],
            // Status kontrak needs a contract (end date): the list opens the contract form next
            'need_contract' => $employee->status === 'kontrak',
        ]);
    }

    /** There is no separate detail page: open the karyawan list with the detail modal. */
    public function show($id)
    {
        return redirect()->route('hrd.employee.index', ['show' => $id]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::withInactive()->findOrFail($id);
        $data = $request->validate($this->rules($employee), $this->messages());

        $oldStatus = $employee->status;
        $contractEnded = false;
        DB::transaction(function () use ($request, $data, $employee, &$contractEnded) {
            $before = EmployeeLog::snapshot($employee);

            $employee->update($this->employeeData($request, $data, $employee));
            $this->syncPositions($employee, $data['position_ids'], $data['primary_position'] ?? null);
            $contractEnded = $employee->endActiveContractIfNotKontrak();

            $employee->unsetRelations();
            EmployeeLog::recordDiff($employee->id, $before, EmployeeLog::snapshot($employee));
            if ($contractEnded) {
                EmployeeLog::note($employee->id, 'kontrak', 'Kontrak', 'Aktif', 'Diakhiri karena status menjadi ' . $employee->status);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diperbarui' . ($contractEnded ? '. Kontrak aktif ikut diakhiri.' : ''),
            // Became kontrak without an active contract: the list opens the contract form next
            'need_contract' => $employee->status === 'kontrak' && $oldStatus !== 'kontrak'
                && !$employee->contracts()->where('status', 'active')->exists(),
        ]);
    }

    public function getDetails($id)
    {
        $employee = Employee::withInactive()->with(['positions.divisions', 'user.roles:id,name'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee->toArray() + [
                'masa_kerja' => $employee->masaKerja(),
                'belum_lengkap' => $employee->dataBelumLengkap(),
                'nonaktif_alasan_label' => Employee::NONAKTIF_ALASAN[$employee->nonaktif_alasan] ?? null,
                'status_pernikahan_label' => Employee::STATUS_PERNIKAHAN[$employee->status_pernikahan] ?? null,
                'ptkp' => $employee->ptkp(),
                'perusahaan_daftar' => $employee->daftarPerusahaan(),
                'akun' => $employee->user ? [
                    'name' => $employee->user->name,
                    'email' => $employee->user->email,
                    'roles' => $employee->user->roles->pluck('name')->all(),
                ] : null,
            ],
        ]);
    }

    /** Riwayat perubahan data karyawan (detail modal). */
    public function logs($id)
    {
        $employee = Employee::withInactive()->findOrFail($id);

        return response()->json(EmployeeLog::with('user:id,name')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn($log) => [
                'waktu' => $log->created_at->locale('id')->isoFormat('D MMM YYYY HH:mm'),
                'oleh' => $log->user->name ?? '-',
                'aksi' => EmployeeLog::AKSI_LABEL[$log->aksi] ?? $log->aksi,
                'kolom' => $log->kolom,
                'sebelum' => $log->sebelum,
                'sesudah' => $log->sesudah,
            ]));
    }

    /**
     * Return next `no_induk` value in format YYMMXXX where
     * YY = last two digits of year, MM = month, XXX = sequential 3-digit number
     */
    public function nextNoInduk()
    {
        return response()->json([
            'success' => true,
            'next' => $this->generateNoInduk()
        ]);
    }
}
