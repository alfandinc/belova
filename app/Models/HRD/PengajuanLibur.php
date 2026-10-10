<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanLibur extends Model
{
    use HasFactory;

    protected $table = 'hrd_pengajuan_libur';

    protected $fillable = [
        'employee_id',
        'jenis_libur',
        'tanggal_masuk_pengganti',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_hari',
        'tanggal_mulai_diajukan',
        'tanggal_selesai_diajukan',
        'total_hari_diajukan',
        'alasan',
        'status_manager',
        'notes_manager',
        'tanggal_persetujuan_manager',
        'status_hrd',
        'notes_hrd',
        'tanggal_persetujuan_hrd'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_mulai_diajukan' => 'date',
        'tanggal_selesai_diajukan' => 'date',
        'tanggal_masuk_pengganti' => 'array',
        'tanggal_persetujuan_manager' => 'datetime',
        'tanggal_persetujuan_hrd' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withInactive();
    }

    /**
     * Non-rejected ganti libur requests of an employee that name the Sunday / holiday worked.
     */
    public function scopeGantiLiburAktif($query, $employeeId = null)
    {
        return $query->where('jenis_libur', 'ganti_libur')
            ->when($employeeId, fn($q) => $q->whereIn('employee_id', (array) $employeeId))
            ->where(fn($q) => $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak'))
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak'));
    }

    /**
     * Sunday / holiday dates (Y-m-d) already claimed as pengganti by the employee's ganti libur requests.
     */
    public static function claimedHariMasuk($employeeId, $excludeId = null): array
    {
        return static::gantiLiburAktif($employeeId)
            ->whereNotNull('tanggal_masuk_pengganti')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get()
            ->pluck('tanggal_masuk_pengganti')
            ->flatten()
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->all();
    }

    /**
     * Worked Sundays / holidays (Y-m-d, oldest first, scheduled future days included) not yet claimed by an
     * active ganti libur. This list IS the balance: jatah ganti libur is counted from the schedule, never stored.
     * Only days from LiburNasional::gantiLiburMulai() count (see isHariGantiLibur).
     * $excludeId: a request being edited, whose claimed days become free again.
     */
    public static function hariMasukBelumDipakai($employeeId, ?array $holidays = null, $excludeId = null): array
    {
        $holidays ??= LiburNasional::namesByDate();
        $claimed = static::claimedHariMasuk($employeeId, $excludeId);

        return EmployeeSchedule::where('employee_id', $employeeId)
            ->whereDate('date', '>=', LiburNasional::gantiLiburMulai())
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->unique()
            ->filter(fn($d) => LiburNasional::isHariGantiLibur($d, $holidays) && !in_array($d, $claimed, true))
            ->values()
            ->all();
    }

    /**
     * Ganti libur balance, counted from the schedule: saldo = worked Sundays / holidays (up to today) not yet
     * claimed, terjadwal = scheduled ones still ahead (usable once worked), diajukan = days claimed by
     * requests still waiting for approval (already out of saldo).
     */
    public static function ringkasanGantiLibur($employeeId, ?array $holidays = null): array
    {
        return static::ringkasanGantiLiburBatch([$employeeId], $holidays)[$employeeId];
    }

    /**
     * ringkasanGantiLibur for many employees in two queries (schedule grid): [employee_id => ringkasan].
     * Same rule as hariMasukBelumDipakai.
     */
    public static function ringkasanGantiLiburBatch(array $employeeIds, ?array $holidays = null): array
    {
        $holidays ??= LiburNasional::namesByDate();
        $today = \Carbon\Carbon::today()->toDateString();
        $toDate = fn($d) => \Carbon\Carbon::parse($d)->toDateString();

        $aktif = static::gantiLiburAktif($employeeIds)->get()->groupBy('employee_id');
        $worked = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', LiburNasional::gantiLiburMulai())
            ->get(['employee_id', 'date'])
            ->map(fn($s) => [$s->employee_id, $toDate($s->date)])
            ->filter(fn($x) => LiburNasional::isHariGantiLibur($x[1], $holidays))
            ->groupBy(fn($x) => $x[0]);

        $out = [];
        foreach ($employeeIds as $id) {
            $requests = $aktif[$id] ?? collect();
            $claimed = $requests->flatMap(fn($p) => (array) $p->tanggal_masuk_pengganti)->map($toDate)->all();
            $belumDipakai = collect($worked[$id] ?? [])->pluck(1)->unique()
                ->reject(fn($d) => in_array($d, $claimed, true));

            $out[$id] = [
                'saldo' => $belumDipakai->filter(fn($d) => $d <= $today)->count(),
                'terjadwal' => $belumDipakai->filter(fn($d) => $d > $today)->count(),
                'diajukan' => (int) $requests->filter(fn($p) => $p->status_hrd !== 'disetujui')
                    ->sum(fn($p) => count((array) $p->tanggal_masuk_pengganti)),
            ];
        }

        return $out;
    }

    public static function saldoGantiLibur($employeeId, ?array $holidays = null): int
    {
        return static::ringkasanGantiLibur($employeeId, $holidays)['saldo'];
    }

    /**
     * Masuk dulu baru libur: the Sunday / holiday must already be worked (not after today) and lie before
     * the first day of the ganti libur.
     * $bolehTerjadwal: HRD planning from the schedule grid may also pair a Sunday / holiday that is only
     * scheduled (not worked yet), as long as it still comes before the libur.
     */
    public static function hariMasukSebelumLibur($hariMasuk, $tanggalLibur, bool $bolehTerjadwal = false): bool
    {
        $hariMasuk = \Carbon\Carbon::parse($hariMasuk)->toDateString();

        return ($bolehTerjadwal || $hariMasuk <= \Carbon\Carbon::today()->toDateString())
            && $hariMasuk < \Carbon\Carbon::parse($tanggalLibur)->toDateString();
    }

    /**
     * The active ganti libur request that uses $date (Sunday / holiday worked) as its pengganti, if any.
     */
    public static function claimOf($employeeId, $date): ?self
    {
        $date = \Carbon\Carbon::parse($date)->toDateString();

        return static::gantiLiburAktif($employeeId)
            ->whereNotNull('tanggal_masuk_pengganti')
            ->get()
            ->first(fn($p) => collect($p->tanggal_masuk_pengganti)
                ->contains(fn($d) => \Carbon\Carbon::parse($d)->toDateString() === $date));
    }

    /**
     * Error message for removing the work on a claimed Sunday / holiday, or null when it is free.
     */
    public static function claimedError($employeeId, $date): ?string
    {
        $claim = static::claimOf($employeeId, $date);
        if (!$claim) {
            return null;
        }
        $fmt = fn($d) => \Carbon\Carbon::parse($d)->locale('id')->isoFormat('ddd D MMM YYYY');
        $nama = Employee::whereKey($employeeId)->value('nama') ?? ('#' . $employeeId);

        return "Hari masuk {$nama} tanggal " . $fmt($date) . ' sudah dipakai ganti libur tanggal ' . $fmt($claim->tanggal_mulai)
            . ($claim->total_hari > 1 ? ' – ' . $fmt($claim->tanggal_selesai) : '')
            . '. Batalkan / tolak ganti libur tersebut terlebih dahulu.';
    }

    /**
     * History of every worked Sunday / holiday (from LiburNasional::gantiLiburMulai()) paired with the ganti
     * libur that used it, newest first. Status: 'tersedia' / 'terjadwal' (counts in the balance, past / ahead),
     * 'diajukan' (claimed, waiting for approval), 'dipakai' (claimed and approved). Ganti libur without a
     * worked date (from before dates were recorded) get a row with masuk null.
     * Returned with the balance summary (ringkasanGantiLibur), ready to merge into a JSON response.
     */
    public static function rincianGantiLibur(int $employeeId, array $holidays): array
    {
        $fmt = fn($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('D, j M Y');
        $today = \Carbon\Carbon::today()->toDateString();
        $mulai = LiburNasional::gantiLiburMulai();

        $aktif = static::gantiLiburAktif($employeeId)->orderByDesc('tanggal_mulai')->get();
        $libur = fn($p) => [
            'pengajuan_id' => $p->id,
            'jumlah_hari' => (int) $p->total_hari,
            'libur_mulai' => \Carbon\Carbon::parse($p->tanggal_mulai)->toDateString(),
            'libur' => $fmt($p->tanggal_mulai) . ($p->total_hari > 1 ? ' – ' . $fmt($p->tanggal_selesai) : ''),
            'status' => $p->status_hrd === 'disetujui' ? 'dipakai' : 'diajukan',
        ];
        $claimedBy = [];
        foreach ($aktif as $p) {
            foreach ((array) $p->tanggal_masuk_pengganti as $d) {
                if (($d = \Carbon\Carbon::parse($d)->toDateString()) >= $mulai) {
                    $claimedBy[$d] = $p;
                }
            }
        }

        $shifts = EmployeeSchedule::with('shift:id,name')->where('employee_id', $employeeId)
            ->whereDate('date', '>=', $mulai)->get(['date', 'shift_id'])
            ->groupBy(fn($s) => \Carbon\Carbon::parse($s->date)->toDateString())
            ->filter(fn($items, $date) => LiburNasional::isHariGantiLibur($date, $holidays))
            ->map(fn($items) => $items->map(fn($s) => $s->shift->name ?? null)->filter()->unique()->implode(', '));

        // Worked days in the schedule plus claimed days whose schedule is gone, so no ganti libur drops out of the history
        $rows = $shifts->keys()->merge(array_keys($claimedBy))->unique()
            ->map(function ($date) use ($fmt, $today, $claimedBy, $holidays, $libur, $shifts) {
                $p = $claimedBy[$date] ?? null;
                return [
                    'sort' => $date,
                    'date' => $date,
                    'masuk' => $fmt($date),
                    'keterangan' => $holidays[$date] ?? (\Carbon\Carbon::parse($date)->isSunday() ? 'Hari Minggu' : null),
                    'shift' => $shifts[$date] ?? null,
                    // Pairing made before "masuk dulu baru libur" was enforced: worked day on / after the libur
                    'terbalik' => $p && $date >= \Carbon\Carbon::parse($p->tanggal_mulai)->toDateString(),
                ] + ($p ? $libur($p) : [
                    'pengajuan_id' => null,
                    'libur' => null,
                    'status' => $date > $today ? 'terjadwal' : 'tersedia',
                ]);
            })
            ->values()
            ->merge($aktif->filter(fn($p) => empty($p->tanggal_masuk_pengganti)
                && \Carbon\Carbon::parse($p->tanggal_mulai)->toDateString() >= $mulai)->map(fn($p) => [
                'sort' => \Carbon\Carbon::parse($p->tanggal_mulai)->toDateString(),
                'date' => null,
                'masuk' => null,
                'keterangan' => null,
                'shift' => null,
            ] + $libur($p)))
            ->sortByDesc('sort')
            ->values();

        return [
            'ganti_libur_tanggal' => $rows->all(),
            'ganti_libur_ringkasan' => static::ringkasanGantiLibur($employeeId, $holidays),
        ];
    }

    /**
     * Calculate the total days between start and end dates when saving
     */
    protected static function boot()
    {
        parent::boot();
        
        // Force correct day calculation on both create and update
        static::saving(function ($model) {
            // Always recalculate total_hari for consistency
            $start = \Carbon\Carbon::parse($model->tanggal_mulai)->startOfDay();
            $end = \Carbon\Carbon::parse($model->tanggal_selesai)->startOfDay();
            
            // Calculate days by counting dates between start and end (inclusive)
            // Manual calculation to ensure accuracy:
            // 1. Get all days between the two dates
            // 2. Count them + 1 to include the start date
            $startTimestamp = $start->getTimestamp();
            $endTimestamp = $end->getTimestamp();
            $totalHari = (int)round(($endTimestamp - $startTimestamp) / 86400) + 1;
            
            // Force positive value (absolute) to prevent negative days
            $totalHari = abs($totalHari);
            
            // Safety check - ensure at least 1 day
            if ($totalHari < 1) {
                $totalHari = 1;
            }
            
            $model->total_hari = $totalHari;
        });
    }
}
