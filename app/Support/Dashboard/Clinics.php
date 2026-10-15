<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The clinics as the dashboard shows them: a short name and a fixed color per clinic (the same hues as
 * the login page / sidebar accent: Premiere blue, Skin pink, Dental green), so a clinic has one color
 * in every widget. Colors are CSS variables with a dark-theme value in dashboard/partials/widget_styles.
 */
class Clinics
{
    /** keyword in erm_klinik.nama => short name + color token */
    private const KNOWN = [
        'premiere' => ['short' => 'Premiere', 'color' => 'var(--dw-klinik-premiere)'],
        'skin' => ['short' => 'Skin', 'color' => 'var(--dw-klinik-skin)'],
        'dental' => ['short' => 'Dental', 'color' => 'var(--dw-klinik-dental)'],
    ];

    private static ?Collection $all = null;

    /** id => (object) [id, nama, short, color], in the fixed order Premiere, Skin, Dental, then others. */
    public static function all(): Collection
    {
        return self::$all ??= DB::table('erm_klinik')->orderBy('id')->get(['id', 'nama'])
            ->map(function ($klinik) {
                $known = self::match($klinik->nama);

                return (object) [
                    'id' => (int) $klinik->id,
                    'nama' => $klinik->nama,
                    'short' => $known['short'] ?? $klinik->nama,
                    'color' => $known['color'] ?? Fmt::OTHER,
                    'rank' => $known ? array_search($known['short'], array_column(self::KNOWN, 'short')) : 99,
                ];
            })
            ->sortBy('rank')
            ->keyBy('id');
    }

    /** Color for a clinic name (or id); neutral gray when unknown / no clinic. */
    public static function color(int|string|null $klinik): string
    {
        if (is_int($klinik) || ctype_digit((string) $klinik)) {
            return self::all()->get((int) $klinik)->color ?? Fmt::OTHER;
        }

        return self::match((string) $klinik)['color'] ?? Fmt::OTHER;
    }

    public static function short(int|string|null $klinik): string
    {
        if (is_int($klinik) || ctype_digit((string) $klinik)) {
            return self::all()->get((int) $klinik)->short ?? 'Tanpa klinik';
        }

        return self::match((string) $klinik)['short'] ?? ($klinik ?: 'Tanpa klinik');
    }

    /**
     * Per-clinic split of one number, for x-dash.split / x-dash.stat :clinics.
     * $byKlinik: klinik_id => value (other / null keys are summed into "Tanpa klinik").
     * Every clinic is listed (also at 0) so the clinics always appear in the same order.
     *
     * @return list<array{label: string, value: string, amount: float, pct: float, color: string}>
     */
    public static function split(iterable $byKlinik, ?callable $format = null): array
    {
        $format ??= fn ($v) => Fmt::num($v);
        $values = collect($byKlinik)->map(fn ($v) => (float) $v);
        $total = (float) $values->sum();

        $rows = self::all()->map(fn ($k) => [
            'label' => $k->short,
            'value' => $format($values[$k->id] ?? 0),
            'amount' => (float) ($values[$k->id] ?? 0),
            'pct' => Fmt::share($values[$k->id] ?? 0, $total),
            'color' => $k->color,
        ])->values();

        $unassigned = (float) $values->reject(fn ($v, $id) => self::all()->has((int) $id))->sum();
        if ($unassigned > 0) {
            $rows->push([
                'label' => 'Tanpa klinik',
                'value' => $format($unassigned),
                'amount' => $unassigned,
                'pct' => Fmt::share($unassigned, $total),
                'color' => Fmt::OTHER,
            ]);
        }

        return $rows->all();
    }

    private static function match(string $nama): ?array
    {
        $nama = strtolower($nama);
        foreach (self::KNOWN as $keyword => $known) {
            if (str_contains($nama, $keyword)) {
                return $known;
            }
        }

        return null;
    }
}
