<?php

namespace App\Models\HRD;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LiburNasional extends Model
{
    protected $table = 'hrd_libur_nasional';

    protected $fillable = [
        'tanggal',
        'nama',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Holiday names keyed by Y-m-d, optionally limited to a date range.
     */
    public static function namesByDate($from = null, $to = null): array
    {
        return static::query()
            ->when($from, fn($q) => $q->whereDate('tanggal', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('tanggal', '<=', $to))
            ->get()
            ->mapWithKeys(fn($h) => [$h->tanggal->toDateString() => $h->nama])
            ->all();
    }

    /**
     * A day that earns jatah ganti libur when worked: Sunday or a national holiday.
     * Pass $holidays (from namesByDate) when checking many dates to avoid one query per date.
     */
    public static function isHariGantiLibur($date, ?array $holidays = null): bool
    {
        $day = Carbon::parse($date);
        if ($day->isSunday()) {
            return true;
        }

        return $holidays !== null
            ? isset($holidays[$day->toDateString()])
            : static::whereDate('tanggal', $day->toDateString())->exists();
    }
}
