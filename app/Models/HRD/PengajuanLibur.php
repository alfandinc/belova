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
     * Worked Sundays / holidays (Y-m-d, oldest first, scheduled future days included) that still back the
     * employee's ganti libur balance. Dates were only tracked per request recently, so older requests used
     * days without naming them: the balance is the truth, and only the newest (saldo − days reserved by
     * requests not yet deducted) unclaimed days count. Older unclaimed days are treated as already used.
     * $excludeId: a request being edited, whose claimed days become free again.
     */
    public static function hariMasukBelumDipakai($employeeId, ?array $holidays = null, $excludeId = null): array
    {
        $holidays ??= LiburNasional::namesByDate();
        $aktif = static::gantiLiburAktif($employeeId)->get();
        $excluded = $aktif->firstWhere('id', $excludeId);
        $aktif = $aktif->reject(fn($p) => $excluded && $p->id === $excluded->id);

        $claimed = $aktif->flatMap(fn($p) => (array) $p->tanggal_masuk_pengganti)
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())->all();
        // Requests not yet approved by HRD still sit in the saldo; an excluded, already deducted request gives its days back
        $reserved = (int) $aktif->filter(fn($p) => $p->status_hrd !== 'disetujui')->sum('total_hari');
        $refund = $excluded && $excluded->status_hrd === 'disetujui' ? (int) $excluded->total_hari : 0;
        $count = max(0, (int) JatahLibur::where('employee_id', $employeeId)->value('jatah_ganti_libur') - $reserved + $refund);

        $unclaimed = EmployeeSchedule::where('employee_id', $employeeId)
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->unique()
            ->filter(fn($d) => LiburNasional::isHariGantiLibur($d, $holidays) && !in_array($d, $claimed, true))
            ->values();

        return $count > 0 ? $unclaimed->slice(-$count)->values()->all() : [];
    }

    /**
     * How the ganti libur balance is made up: saldo = bisa_dipakai (worked days, past) + belum_dikerjakan
     * (scheduled days still ahead, counted when scheduled) + diajukan (requests not yet deducted)
     * + tanpa_tanggal (old / manual balance without a worked day). sinkron is false only when the saldo is
     * below the days already requested, so the parts cannot add up.
     */
    public static function ringkasanGantiLibur($employeeId, ?array $holidays = null): array
    {
        $saldo = (int) JatahLibur::where('employee_id', $employeeId)->value('jatah_ganti_libur');
        $diajukan = (int) static::gantiLiburAktif($employeeId)
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'disetujui'))
            ->sum('total_hari');
        $backed = collect(static::hariMasukBelumDipakai($employeeId, $holidays));
        $today = \Carbon\Carbon::today()->toDateString();
        $belumDikerjakan = $backed->filter(fn($d) => $d > $today)->count();

        return [
            'saldo' => $saldo,
            'bisa_dipakai' => $backed->count() - $belumDikerjakan,
            'belum_dikerjakan' => $belumDikerjakan,
            'diajukan' => $diajukan,
            'tanpa_tanggal' => max(0, $saldo - $diajukan - $backed->count()),
            'sinkron' => $saldo >= $diajukan,
        ];
    }

    /** Part of the ganti libur balance not backed by any worked day (old / manual balance). */
    public static function saldoTanpaTanggal($employeeId, ?array $holidays = null): int
    {
        return static::ringkasanGantiLibur($employeeId, $holidays)['tanpa_tanggal'];
    }

    /**
     * Masuk dulu baru libur: the Sunday / holiday must already be worked (not after today) and lie before
     * the first day of the ganti libur.
     */
    public static function hariMasukSebelumLibur($hariMasuk, $tanggalLibur): bool
    {
        $hariMasuk = \Carbon\Carbon::parse($hariMasuk)->toDateString();

        return $hariMasuk <= \Carbon\Carbon::today()->toDateString()
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
