<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The clinics as the dashboard shows them: short name, logo and a fixed color per clinic (the same hues as
 * the login page / sidebar accent: Premiere blue, Skin pink, Dental green). Widgets show each clinic in its
 * own section (x-dash.clinic) instead of mixing clinics in one number. Colors are CSS variables with a
 * dark-theme value in dashboard/partials/widget_styles.
 */
class Clinics
{
    /** keyword in erm_klinik.nama => short name + color token, in display order */
    private const KNOWN = [
        'premiere' => ['short' => 'Premiere', 'color' => 'var(--dw-klinik-premiere)'],
        'skin' => ['short' => 'Skin', 'color' => 'var(--dw-klinik-skin)'],
        'dental' => ['short' => 'Dental', 'color' => 'var(--dw-klinik-dental)'],
    ];

    private static ?Collection $all = null;

    /** id => (object) [id, nama, short, color, logo (url|null)], ordered Premiere, Skin, Dental, then others. */
    public static function all(): Collection
    {
        return self::$all ??= DB::table('erm_klinik')->orderBy('id')->get(['id', 'nama', 'logo'])
            ->map(function ($klinik) {
                $keyword = self::keyword($klinik->nama);

                return (object) [
                    'id' => (int) $klinik->id,
                    'nama' => $klinik->nama,
                    'short' => $keyword ? self::KNOWN[$keyword]['short'] : $klinik->nama,
                    'color' => $keyword ? self::KNOWN[$keyword]['color'] : Fmt::OTHER,
                    'logo' => $klinik->logo ? asset('storage/' . $klinik->logo) : null,
                    'rank' => $keyword ? array_search($keyword, array_keys(self::KNOWN)) : 99,
                ];
            })
            ->sortBy('rank')
            ->keyBy('id');
    }

    /** Pseudo clinic for rows without a clinic (e.g. direct sales), shown after the real clinics. */
    public static function none(): object
    {
        return (object) ['id' => null, 'nama' => 'Tanpa klinik', 'short' => 'Tanpa klinik', 'color' => Fmt::OTHER, 'logo' => null];
    }

    /** The clinic of an id ('' / null / unknown => none()). */
    public static function find(int|string|null $id): object
    {
        return ($id !== null && $id !== '') ? (self::all()->get((int) $id) ?? self::none()) : self::none();
    }

    /**
     * One entry per clinic for x-dash.clinic sections: ['klinik' => clinic] + $build($rowsOfThatClinic, $klinik).
     * $rows: rows with a klinik_id property. Every clinic is listed (also without rows, so the sections
     * keep their place) unless $onlyWithData; rows without a known clinic form a last "Tanpa klinik" section.
     */
    public static function sections(iterable $rows, callable $build, bool $onlyWithData = false): array
    {
        $groups = collect($rows)->groupBy(fn ($r) => self::find($r->klinik_id ?? null)->id ?? 'none');

        $sections = self::all()
            ->when($onlyWithData, fn ($all) => $all->filter(fn ($k) => $groups->has($k->id)))
            ->map(fn ($k) => ['klinik' => $k] + $build($groups->get($k->id, collect()), $k))
            ->values();

        if ($groups->has('none')) {
            $sections->push(['klinik' => self::none()] + $build($groups->get('none'), self::none()));
        }

        return $sections->all();
    }

    public static function color(int|string|null $id): string
    {
        return self::find($id)->color;
    }

    private static function keyword(string $nama): ?string
    {
        $nama = strtolower($nama);
        foreach (array_keys(self::KNOWN) as $keyword) {
            if (str_contains($nama, $keyword)) {
                return $keyword;
            }
        }

        return null;
    }
}
