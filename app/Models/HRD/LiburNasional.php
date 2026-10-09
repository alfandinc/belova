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

    private static ?string $gantiLiburMulai = null;

    /**
     * Ganti libur is tracked from this date on (Y-m-d, "ganti_libur_mulai" in storage/app/hrd_config.json,
     * default 2026-07-01): earlier Sundays / holidays neither earn ganti libur nor can be named as hari masuk
     * (older data was inconsistent).
     */
    public static function gantiLiburMulai(): string
    {
        return self::$gantiLiburMulai ??= \App\Helpers\HrdConfig::getGantiLiburMulai();
    }

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
     * A day that earns jatah ganti libur when worked: Sunday or a national holiday, from gantiLiburMulai() on.
     * Pass $holidays (from namesByDate) when checking many dates to avoid one query per date.
     */
    public static function isHariGantiLibur($date, ?array $holidays = null): bool
    {
        $day = Carbon::parse($date);
        if ($day->toDateString() < self::gantiLiburMulai()) {
            return false;
        }
        if ($day->isSunday()) {
            return true;
        }

        return $holidays !== null
            ? isset($holidays[$day->toDateString()])
            : static::whereDate('tanggal', $day->toDateString())->exists();
    }
}
