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
     * Part of the ganti libur balance not backed by any scheduled Sunday / holiday (old manual balance).
     * Backed = unclaimed scheduled days + days claimed by requests whose balance is not deducted yet.
     */
    private function saldoTanpaTanggal(int $employeeId, int $saldo, array $holidays): int
    {
        $aktif = PengajuanLibur::gantiLiburAktif($employeeId)->whereNotNull('tanggal_masuk_pengganti')->get();
        $claimed = $aktif->pluck('tanggal_masuk_pengganti')->flatten()
            ->map(fn($d) => Carbon::parse($d)->toDateString())->all();
        $pendingClaimed = $aktif->filter(fn($p) => $p->status_hrd !== 'disetujui')
            ->sum(fn($p) => count((array) $p->tanggal_masuk_pengganti));

        $unclaimed = EmployeeSchedule::where('employee_id', $employeeId)
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->filter(fn($d) => LiburNasional::isHariGantiLibur($d, $holidays) && !in_array($d, $claimed, true))
            ->count();

        return max(0, $saldo - $unclaimed - $pendingClaimed);
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
        ->leftJoin('hrd_employee as e', 'hrd_jatah_libur.employee_id', '=', 'e.id')
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
                $tanpaTanggal = $this->saldoTanpaTanggal($jatah->employee_id, (int) $jatah->jatah_ganti_libur, $holidays);

                return (int) $jatah->jatah_ganti_libur . ($tanpaTanggal > 0
                    ? ' <span class="badge badge-warning" title="Saldo tanpa hari masuk di jadwal; karyawan tidak bisa memakainya sampai hari masuknya ditambahkan">' . $tanpaTanggal . ' tanpa tanggal</span>'
                    : '');
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

        return response()->json($jatahLibur->toArray() + [
            'ganti_libur_tanpa_tanggal' => $this->saldoTanpaTanggal($jatahLibur->employee_id, (int) $jatahLibur->jatah_ganti_libur, LiburNasional::namesByDate()),
        ]);
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
