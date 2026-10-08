<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\HrdConfig;

class JatahLiburController extends Controller
{
    public function index()
    {
        return view('hrd.master.jatah-libur.index', [
            'shifts' => Shift::where('active', true)->orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']),
            'liburNasional' => LiburNasional::namesByDate(null, Carbon::today()),
        ]);
    }

    private function hariMasukRules(): array
    {
        return [
            'hari_masuk' => 'nullable|array',
            'hari_masuk.*.date' => 'required|date|before_or_equal:today|distinct',
            'hari_masuk.*.shift_id' => 'required|exists:hrd_shifts,id',
        ];
    }

    private function hariMasukMessages(): array
    {
        return [
            'hari_masuk.*.date.required' => 'Tanggal hari masuk wajib diisi.',
            'hari_masuk.*.date.before_or_equal' => 'Tanggal hari masuk tidak boleh setelah hari ini.',
            'hari_masuk.*.date.distinct' => 'Tanggal hari masuk tidak boleh sama.',
            'hari_masuk.*.shift_id.required' => 'Shift hari masuk wajib dipilih.',
        ];
    }

    /**
     * History of every worked Sunday / holiday paired with the ganti libur that used it, newest first.
     * Status: 'tersedia' / 'terjadwal' (backs the balance, past / ahead), 'diajukan' (claimed, not yet
     * approved by HRD), 'dipakai' (claimed and deducted), 'lama' (used before days were tracked per request,
     * see PengajuanLibur::hariMasukBelumDipakai). Ganti libur without a worked date get a row with masuk null.
     * Returned with the balance summary (PengajuanLibur::ringkasanGantiLibur), ready to merge into a JSON response.
     */
    private function rincianGantiLibur(int $employeeId, array $holidays): array
    {
        $fmt = fn($d) => Carbon::parse($d)->locale('id')->translatedFormat('D, j M Y');
        $today = Carbon::today()->toDateString();
        $tersedia = PengajuanLibur::hariMasukBelumDipakai($employeeId, $holidays);

        $aktif = PengajuanLibur::gantiLiburAktif($employeeId)->orderByDesc('tanggal_mulai')->get();
        $libur = fn($p) => [
            'pengajuan_id' => $p->id,
            'jumlah_hari' => (int) $p->total_hari,
            'libur_mulai' => Carbon::parse($p->tanggal_mulai)->toDateString(),
            'libur' => $fmt($p->tanggal_mulai) . ($p->total_hari > 1 ? ' – ' . $fmt($p->tanggal_selesai) : ''),
            'status' => $p->status_hrd === 'disetujui' ? 'dipakai' : 'diajukan',
        ];
        $claimedBy = [];
        foreach ($aktif as $p) {
            foreach ((array) $p->tanggal_masuk_pengganti as $d) {
                $claimedBy[Carbon::parse($d)->toDateString()] = $p;
            }
        }

        $shifts = EmployeeSchedule::with('shift:id,name')->where('employee_id', $employeeId)->get(['date', 'shift_id'])
            ->groupBy(fn($s) => Carbon::parse($s->date)->toDateString())
            ->filter(fn($items, $date) => LiburNasional::isHariGantiLibur($date, $holidays))
            ->map(fn($items) => $items->map(fn($s) => $s->shift->name ?? null)->filter()->unique()->implode(', '));

        // Worked days in the schedule plus claimed days whose schedule is gone, so no ganti libur drops out of the history
        $rows = $shifts->keys()->merge(array_keys($claimedBy))->unique()
            ->map(function ($date) use ($fmt, $today, $tersedia, $claimedBy, $holidays, $libur, $shifts) {
                $p = $claimedBy[$date] ?? null;
                return [
                    'sort' => $date,
                    'date' => $date,
                    'masuk' => $fmt($date),
                    'keterangan' => $holidays[$date] ?? (Carbon::parse($date)->isSunday() ? 'Hari Minggu' : null),
                    'shift' => $shifts[$date] ?? null,
                    // Pairing made before "masuk dulu baru libur" was enforced: worked day on / after the libur
                    'terbalik' => $p && $date >= Carbon::parse($p->tanggal_mulai)->toDateString(),
                ] + ($p ? $libur($p) : [
                    'pengajuan_id' => null,
                    'libur' => null,
                    'status' => !in_array($date, $tersedia, true) ? 'lama' : ($date > $today ? 'terjadwal' : 'tersedia'),
                ]);
            })
            ->values()
            ->merge($aktif->filter(fn($p) => empty($p->tanggal_masuk_pengganti))->map(fn($p) => [
                'sort' => Carbon::parse($p->tanggal_mulai)->toDateString(),
                'date' => null,
                'masuk' => null,
                'keterangan' => null,
                'shift' => null,
            ] + $libur($p)))
            ->sortByDesc('sort')
            ->values();

        $ringkasan = PengajuanLibur::ringkasanGantiLibur($employeeId, $holidays);

        return [
            'ganti_libur_tanggal' => $rows->all(),
            'ganti_libur_tanpa_tanggal' => $ringkasan['tanpa_tanggal'],
            'ganti_libur_ringkasan' => $ringkasan,
        ];
    }

    /**
     * Manually added jatah ganti libur must name the Sundays / holidays actually worked, one per day added;
     * those dates are added to the employee's schedule. Must run inside a transaction.
     */
    private function tambahHariMasuk(JatahLibur $jatah, int $tambah, array $rows): void
    {
        if (count($rows) !== max(0, $tambah)) {
            throw ValidationException::withMessages(['hari_masuk' => $tambah > 0
                ? 'Jatah ganti libur bertambah ' . $tambah . ' hari, pilih tepat ' . $tambah . ' tanggal hari masuk (dipilih ' . count($rows) . ').'
                : 'Tanggal hari masuk hanya diisi saat jatah ganti libur ditambah.']);
        }

        $holidays = LiburNasional::namesByDate();

        foreach ($rows as $row) {
            $this->buatHariMasuk($jatah, $row, $holidays);
        }
    }

    /**
     * Put a worked Sunday / holiday (['date', 'shift_id']) into the employee's schedule without touching the
     * balance; the caller accounts for it. Must run inside a transaction.
     */
    private function buatHariMasuk(JatahLibur $jatah, array $row, array $holidays): void
    {
        $date = Carbon::parse($row['date'])->toDateString();
        $label = Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y');

        if (!LiburNasional::isHariGantiLibur($date, $holidays)) {
            throw ValidationException::withMessages(['hari_masuk' => $label . ' bukan hari Minggu atau libur nasional.']);
        }
        if (EmployeeSchedule::where('employee_id', $jatah->employee_id)->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages(['hari_masuk' => $label . ' sudah ada di jadwal karyawan (jatahnya sudah dihitung).']);
        }

        EmployeeSchedule::create([
            'employee_id' => $jatah->employee_id,
            'date' => $date,
            'shift_id' => $row['shift_id'],
        ]);
    }

    /**
     * Give a date to balance that has none ("tanpa tanggal"): the worked Sunday / holiday is added to the
     * schedule and the balance stays the same, since that day was already counted. Allowed only while the
     * employee still has undated balance. (Filling the day in the schedule grid instead would add +1 again.)
     */
    public function lengkapiHariMasuk(Request $request, $id)
    {
        $request->validate([
            'hari_masuk' => 'required|array|size:1',
            'hari_masuk.0.date' => 'required|date|before_or_equal:today',
            'hari_masuk.0.shift_id' => 'required|exists:hrd_shifts,id',
        ], $this->hariMasukMessages() + [
            'hari_masuk.0.date.required' => 'Tanggal hari masuk wajib diisi.',
            'hari_masuk.0.date.before_or_equal' => 'Tanggal hari masuk tidak boleh setelah hari ini.',
            'hari_masuk.0.shift_id.required' => 'Shift hari masuk wajib dipilih.',
        ]);

        $jatah = DB::transaction(function () use ($request, $id) {
            $jatah = JatahLibur::lockForUpdate()->findOrFail($id);
            $holidays = LiburNasional::namesByDate();
            if (PengajuanLibur::saldoTanpaTanggal($jatah->employee_id, $holidays) < 1) {
                throw ValidationException::withMessages(['hari_masuk' => 'Semua saldo ganti libur sudah punya tanggal hari masuk.']);
            }
            $this->buatHariMasuk($jatah, $request->input('hari_masuk.0'), $holidays);

            return $jatah;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tanggal hari masuk ditambahkan',
        ] + $this->rincianGantiLibur($jatah->employee_id, LiburNasional::namesByDate()));
    }

    public function getLeaveCapacity()
    {
        return response()->json([
            'success' => true,
            'capacity' => HrdConfig::getLeaveDailyCapacity(),
        ]);
    }

    public function updateLeaveCapacity(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1|max:100',
        ]);
        HrdConfig::setLeaveDailyCapacity((int)$request->input('capacity'));
        return response()->json([
            'success' => true,
            'message' => 'Kuota libur harian berhasil diperbarui',
            'capacity' => HrdConfig::getLeaveDailyCapacity(),
        ]);
    }

    public function getData()
    {
        // Build a query that selects jatah libur plus employee fields so DataTables can
        // search and order by employee name or number. Division is derived from employee positions.
        $employeeDivisionSubquery = DB::table('hrd_employee_position as ep')
            ->join('hrd_position as p', 'p.id', '=', 'ep.position_id')
            ->leftJoin('hrd_division as d', 'd.id', '=', 'p.division_id')
            ->selectRaw("ep.employee_id, GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as division")
            ->groupBy('ep.employee_id');

        $jatahLiburQuery = JatahLibur::select([
            'hrd_jatah_libur.*',
            'e.nama as employee_name',
            'e.no_induk as employee_number',
            'ed.division as division'
        ])
        ->from('hrd_jatah_libur')
        ->join('hrd_employee as e', 'hrd_jatah_libur.employee_id', '=', 'e.id')
        ->whereRaw('LOWER(e.status) <> ?', ['tidak aktif'])
        ->leftJoinSub($employeeDivisionSubquery, 'ed', function ($join) {
            $join->on('ed.employee_id', '=', 'e.id');
        });

        return DataTables::of($jatahLiburQuery)
            // If client searches for 'employee_name' or 'employee_number', let DataTables
            // know how to filter those columns via the column names used in the select.
            ->filterColumn('employee_name', function ($query, $keyword) {
                $sql = "e.nama like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('employee_number', function ($query, $keyword) {
                $sql = "e.no_induk like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('employee_name', function ($jatah) {
                return $jatah->employee_name ?? 'N/A';
            })
            ->addColumn('employee_number', function ($jatah) {
                return $jatah->employee_number ?? 'N/A';
            })
            ->addColumn('division', function ($jatah) {
                return $jatah->division ?? 'N/A';
            })
            ->editColumn('jatah_ganti_libur', function ($jatah) use (&$holidays) {
                $holidays ??= LiburNasional::namesByDate();
                $r = PengajuanLibur::ringkasanGantiLibur($jatah->employee_id, $holidays);

                // Same breakdown as the edit modal: saldo = bisa dipakai + belum dikerjakan + diajukan + tanpa tanggal
                $parts = array_filter([
                    $r['bisa_dipakai'] ? '<span class="text-success">' . $r['bisa_dipakai'] . ' bisa dipakai</span>' : null,
                    $r['belum_dikerjakan'] ? $r['belum_dikerjakan'] . ' belum dikerjakan' : null,
                    $r['diajukan'] ? '<span class="text-info">' . $r['diajukan'] . ' diajukan</span>' : null,
                    $r['tanpa_tanggal'] ? '<span class="badge badge-warning" title="Saldo tanpa hari masuk; lengkapi tanggalnya di Edit">' . $r['tanpa_tanggal'] . ' tanpa tanggal</span>' : null,
                    !$r['sinkron'] ? '<span class="badge badge-danger" title="Saldo lebih kecil dari hari yang sedang diajukan">Tidak sinkron</span>' : null,
                ]);

                return '<strong>' . $r['saldo'] . '</strong>' . ($parts ? '<div class="small text-muted">' . implode(' · ', $parts) . '</div>' : '');
            })
            ->addColumn('action', function ($jatah) {
                return '
                    <button type="button" class="btn btn-sm btn-info edit-jatah-libur" data-id="'.$jatah->id.'">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                ';
            })
            ->rawColumns(['jatah_ganti_libur', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(array_merge([
            'employee_id' => 'required|exists:hrd_employee,id|unique:hrd_jatah_libur,employee_id',
            'jatah_cuti_tahunan' => 'required|integer|min:0',
            'jatah_ganti_libur' => 'required|integer|min:0',
        ], $this->hariMasukRules()), $this->hariMasukMessages());

        $jatahLibur = DB::transaction(function () use ($request) {
            $jatahLibur = JatahLibur::create([
                'employee_id' => $request->employee_id,
                'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan,
                'jatah_ganti_libur' => $request->jatah_ganti_libur,
            ]);
            $this->tambahHariMasuk($jatahLibur, (int) $request->jatah_ganti_libur, $request->input('hari_masuk', []));

            return $jatahLibur->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Jatah libur berhasil ditambahkan',
            'data' => $jatahLibur
        ]);
    }

    public function show($id)
    {
        $jatahLibur = JatahLibur::findOrFail($id);

        return response()->json($jatahLibur->toArray() + $this->rincianGantiLibur($jatahLibur->employee_id, LiburNasional::namesByDate()));
    }

    public function update(Request $request, $id)
    {
        $request->validate(array_merge([
            'jatah_cuti_tahunan' => 'required|integer|min:0',
            'jatah_ganti_libur' => 'required|integer|min:0',
        ], $this->hariMasukRules()), $this->hariMasukMessages());

        $jatahLibur = DB::transaction(function () use ($request, $id) {
            $jatahLibur = JatahLibur::lockForUpdate()->findOrFail($id);
            $tambah = (int) $request->jatah_ganti_libur - (int) $jatahLibur->jatah_ganti_libur;
            // Requests waiting for HRD are deducted on approval; a lower saldo would leave them uncovered
            $diajukan = PengajuanLibur::ringkasanGantiLibur($jatahLibur->employee_id)['diajukan'];
            if ($tambah < 0 && (int) $request->jatah_ganti_libur < $diajukan) {
                throw ValidationException::withMessages(['jatah_ganti_libur' => 'Jatah ganti libur tidak boleh kurang dari ' . $diajukan . ' hari yang sedang diajukan.']);
            }
            $this->tambahHariMasuk($jatahLibur, $tambah, $request->input('hari_masuk', []));
            $jatahLibur->update([
                'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan,
                'jatah_ganti_libur' => $request->jatah_ganti_libur,
            ]);

            return $jatahLibur->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Jatah libur berhasil diperbarui',
            'data' => $jatahLibur
        ]);
    }

    /**
     * Re-pair a ganti libur with the Sundays / holidays it replaces: swap one of its worked days ('lama' ->
     * 'baru'), or name all of them for a ganti libur that has none. The balance never changes: the request's
     * days are already counted, so any worked day not claimed by another request can be chosen, including
     * 'lama' days (to record history). The days that back the balance are recomputed from the new pairing.
     */
    public function updateHariMasuk(Request $request, $id)
    {
        $request->validate([
            'pengajuan_id' => 'required|integer',
            'lama' => 'nullable|date',
            'baru' => 'required|array|min:1',
            'baru.*' => 'required|date|distinct',
        ], [
            'baru.*.required' => 'Pilih hari masuk.',
            'baru.*.distinct' => 'Hari masuk tidak boleh sama.',
        ]);

        $fail = fn(string $msg) => throw ValidationException::withMessages(['hari_masuk' => $msg]);

        $jatah = DB::transaction(function () use ($request, $id, $fail) {
            // Locking the balance row serializes every pairing change of this employee
            $jatah = JatahLibur::lockForUpdate()->findOrFail($id);
            $pengajuan = PengajuanLibur::gantiLiburAktif($jatah->employee_id)->lockForUpdate()->find($request->pengajuan_id)
                ?? $fail('Ganti libur tidak ditemukan atau sudah ditolak.');

            $toDate = fn($d) => Carbon::parse($d)->toDateString();
            $current = collect((array) $pengajuan->tanggal_masuk_pengganti)->map($toDate)->values();
            $baru = collect($request->input('baru'))->map($toDate)->values();

            if ($current->isEmpty()) {
                if ($baru->count() !== (int) $pengajuan->total_hari) {
                    $fail('Ganti libur ini ' . (int) $pengajuan->total_hari . ' hari, pilih tepat ' . (int) $pengajuan->total_hari . ' hari masuk.');
                }
                $result = $baru;
            } else {
                $lama = $request->filled('lama') ? $toDate($request->input('lama')) : null;
                if (!$lama || !$current->contains($lama) || $baru->count() !== 1) {
                    $fail('Hari masuk yang diganti tidak valid, muat ulang data.');
                }
                $result = $current->map(fn($d) => $d === $lama ? $baru[0] : $d);
                if ($result->duplicates()->isNotEmpty()) {
                    $fail('Tanggal tersebut sudah dipakai ganti libur ini.');
                }
            }

            $holidays = LiburNasional::namesByDate();
            $claimed = PengajuanLibur::claimedHariMasuk($jatah->employee_id, $pengajuan->id);
            foreach ($result->diff($current) as $date) {
                $label = Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y');
                if (!LiburNasional::isHariGantiLibur($date, $holidays)) {
                    $fail($label . ' bukan hari Minggu / libur nasional.');
                }
                if (!PengajuanLibur::hariMasukSebelumLibur($date, $pengajuan->tanggal_mulai)) {
                    $fail($label . ' belum dikerjakan / tidak sebelum tanggal libur (masuk dulu baru libur).');
                }
                if (!EmployeeSchedule::where('employee_id', $jatah->employee_id)->whereDate('date', $date)->exists()) {
                    $fail($label . ' tidak ada di jadwal karyawan.');
                }
                if (in_array($date, $claimed, true)) {
                    $fail($label . ' sudah dipakai ganti libur lain.');
                }
            }

            $pengajuan->update(['tanggal_masuk_pengganti' => $result->sort()->values()->all()]);

            return $jatah;
        });

        return response()->json([
            'success' => true,
            'message' => 'Hari masuk pengganti diperbarui',
        ] + $this->rincianGantiLibur($jatah->employee_id, LiburNasional::namesByDate()));
    }

    public function getEmployeesWithoutJatahLibur()
    {
        try {
            // Debug information
            Log::info('Starting getEmployeesWithoutJatahLibur method');
            
            // Get all employee IDs that already have jatah libur
            $employeesWithJatahLibur = JatahLibur::pluck('employee_id')->toArray();
            Log::info('Employees with jatah libur: ' . count($employeesWithJatahLibur));
            
            // Check if we have any employees at all
            $allEmployeeCount = Employee::active()->count();
            Log::info('Total employee count: ' . $allEmployeeCount);
            
            // Query employees without jatah libur
            $query = Employee::active();
            
            // Only filter by not in if we have any employees with jatah libur
            if (!empty($employeesWithJatahLibur)) {
                $query->whereNotIn('id', $employeesWithJatahLibur);
            }
            
            // Add necessary fields with proper aliases
            $employees = $query->select(
                'id',
                'nama as name',
                DB::raw('COALESCE(no_induk, CONCAT("EMP", id)) as employee_number')
            )->get();
            
            Log::info('Employees without jatah libur: ' . $employees->count());
            
            // Generate some test data if no employees are found
            if ($employees->isEmpty() && $allEmployeeCount === 0) {
                Log::info('No employees found, returning dummy data for testing');
                
                // Return at least one dummy employee for testing
                return response()->json([
                    [
                        'id' => 999,
                        'name' => 'Dummy Employee (No employees in system)',
                        'employee_number' => 'EMP999'
                    ]
                ]);
            }
            
            return response()->json($employees);
            
        } catch (\Exception $e) {
            Log::error('Error in getEmployeesWithoutJatahLibur: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset annual leave (`jatah_cuti_tahunan`) to 12 for employees
     * whose `tanggal_masuk` is 1 year or older.
     */
    public function resetAnnualLeave()
    {
        try {
            $oneYearAgo = now()->subYear();

            $employees = Employee::active()->get();

            $counts = [
                'total' => $employees->count(),
                'set_12' => 0,
                'set_0' => 0,
                'created' => 0,
                'updated' => 0,
            ];

            foreach ($employees as $employee) {
                $jatah = JatahLibur::firstOrNew(['employee_id' => $employee->id]);
                $isNew = !$jatah->exists;

                // Determine new annual leave based on masa kerja
                if ($employee->tanggal_masuk && $employee->tanggal_masuk <= $oneYearAgo) {
                    $newAnnual = 12;
                } else {
                    $newAnnual = 0;
                }

                // Only update if value changed (or it's a new record)
                $changed = $isNew || ($jatah->jatah_cuti_tahunan !== $newAnnual);

                $jatah->jatah_cuti_tahunan = $newAnnual;
                if (is_null($jatah->jatah_ganti_libur)) {
                    $jatah->jatah_ganti_libur = 0;
                }
                $jatah->save();

                if ($isNew) {
                    $counts['created']++;
                } elseif ($changed) {
                    $counts['updated']++;
                }

                if ($newAnnual === 12) {
                    $counts['set_12']++;
                } else {
                    $counts['set_0']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reset annual leave completed',
                'total_employees' => $counts['total'],
                'set_12' => $counts['set_12'],
                'set_0' => $counts['set_0'],
                'updated' => $counts['updated'],
                'created' => $counts['created']
            ]);
        } catch (\Exception $e) {
            Log::error('Error resetting annual leave: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
