<?php

namespace App\Models\HRD;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Audit trail of the employee schedule (hrd_schedule_logs). Callers take the readable state of a day with
 * stateOf() before and after changing it, then record() the pair; unchanged days are not logged.
 */
class ScheduleLog extends Model
{
    protected $table = 'hrd_schedule_logs';

    protected $fillable = ['employee_id', 'date', 'sebelum', 'sesudah', 'aksi', 'user_id'];

    protected $casts = ['date' => 'date'];

    public const AKSI_LABEL = [
        'jadwal' => 'Ubah jadwal',
        'hapus' => 'Hapus jadwal',
        'copy_minggu' => 'Copy minggu',
        'ganti_libur' => 'Ganti libur',
        'ganti_shift' => 'Pengajuan ganti shift',
        'hapus_shift' => 'Shift master dihapus',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withInactive();
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Readable state of an employee's day: shift names, "Ganti libur (masuk …)" for a ganti libur set by HRD
     * from the schedule, or null when empty.
     */
    public static function stateOf($employeeId, $date): ?string
    {
        $date = Carbon::parse($date)->toDateString();
        $shifts = EmployeeSchedule::with('shift:id,name')
            ->where('employee_id', $employeeId)->whereDate('date', $date)
            ->get()
            ->map(fn($s) => $s->shift->name ?? ('#' . $s->shift_id))
            ->sort()->values();
        if ($shifts->isNotEmpty()) {
            return $shifts->implode(', ');
        }

        $gl = PengajuanLibur::where('employee_id', $employeeId)
            ->where('jenis_libur', 'ganti_libur')
            ->where('alasan', \App\Http\Controllers\HRD\EmployeeScheduleController::GANTI_LIBUR_HRD)
            ->whereDate('tanggal_mulai', $date)
            ->first();
        if ($gl) {
            $masuk = collect((array) $gl->tanggal_masuk_pengganti)->first();
            return 'Ganti libur' . ($masuk ? ' (masuk ' . Carbon::parse($masuk)->locale('id')->isoFormat('D MMM') . ')' : '');
        }

        return null;
    }

    public static function record($employeeId, $date, ?string $sebelum, ?string $sesudah, string $aksi): void
    {
        if ($sebelum === $sesudah) {
            return;
        }

        static::create([
            'employee_id' => $employeeId,
            'date' => Carbon::parse($date)->toDateString(),
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'aksi' => $aksi,
            'user_id' => Auth::id(),
        ]);
    }
}
