<?php

namespace App\Support\Dashboard;

/** Number formatting shared by the dashboard widgets (Indonesian separators). */
class Fmt
{
    /**
     * Categorical colors in fixed order (validated up to 3 series, all pairs, light and dark). Never cycled
     * past 3. CSS variables so the dark theme swaps them (values in dashboard/partials/widget_styles).
     */
    public const SERIES = ['var(--dw-series-1)', 'var(--dw-series-2)', 'var(--dw-series-3)'];

    public const OTHER = 'var(--dw-other)';

    public static function rp(float|int|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    /** Short rupiah for tight places: Rp 1,2 jt / Rp 3,4 M. */
    public static function rpShort(float|int|null $amount): string
    {
        $amount = (float) $amount;
        $abs = abs($amount);

        return match (true) {
            $abs >= 1_000_000_000 => 'Rp ' . number_format($amount / 1_000_000_000, 1, ',', '.') . ' M',
            $abs >= 1_000_000 => 'Rp ' . number_format($amount / 1_000_000, 1, ',', '.') . ' jt',
            $abs >= 1_000 => 'Rp ' . number_format($amount / 1_000, 0, ',', '.') . ' rb',
            default => self::rp($amount),
        };
    }

    public static function num(float|int|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    public static function pct(float|int|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.') . '%';
    }

    /** Share of a total in percent (0 when the total is 0). */
    public static function share(float|int|null $part, float|int|null $total): float
    {
        return (float) $total > 0 ? ((float) $part / (float) $total) * 100 : 0.0;
    }

    /**
     * Change vs a previous value: ['dir' => up|down|flat|new, 'label' => '12,5%', 'good' => bool|null].
     * 'good' is null when there is nothing to compare against.
     */
    public static function trend(float|int|null $current, float|int|null $previous, bool $higherIsBetter = true): array
    {
        $current = (float) $current;
        $previous = (float) $previous;
        $diff = $current - $previous;

        if (abs($diff) < 0.005) {
            return ['dir' => 'flat', 'label' => '0%', 'good' => null];
        }

        if (abs($previous) < 0.005) {
            return ['dir' => 'new', 'label' => 'baru', 'good' => null];
        }

        $up = $diff > 0;

        return [
            'dir' => $up ? 'up' : 'down',
            'label' => number_format(abs($diff / abs($previous)) * 100, 1, ',', '.') . '%',
            'good' => $up === $higherIsBetter,
        ];
    }
}
