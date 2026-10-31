<?php

namespace App\Support\Marketing;

use App\Models\ERM\Pasien;
use App\Models\Marketing\ReferralMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which visit referrals count as Marketing & Sales activity, as set on Admin Panel > Referral Marketing
 * (marketing_referral_mappings). A rule on the whole referral type applies to all its details; a rule on
 * one detail (e.g. Social Media → whatsapp, one Event, one Karyawan) overrides it. No rule = not marketing.
 *
 * A visit's referral is its own (transaction) referral, or the patient's source referral for legacy visits
 * without one (same rule as BillingRowFormatter::referralParts). SQL helpers expect the visit as `v` and
 * its patient as `p`.
 */
class MarketingReferrals
{
    /** Types whose details can get their own rule (walk-in has no detail, a referring patient is too fine-grained). */
    public const DETAIL_TYPES = [
        Pasien::REFERRAL_TYPE_SOCIAL_MEDIA,
        Pasien::REFERRAL_TYPE_MARKETPLACE,
        Pasien::REFERRAL_TYPE_EVENT,
        Pasien::REFERRAL_TYPE_FOLLOW_UP,
        Pasien::REFERRAL_TYPE_EMPLOYEE,
        Pasien::REFERRAL_TYPE_DOKTER,
        Pasien::REFERRAL_TYPE_PARTNERSHIP,
        Pasien::REFERRAL_TYPE_WEBSITE,
        Pasien::REFERRAL_TYPE_GOOGLE_MAPS,
    ];

    /** Types whose detail is a record id (event: kode_event of old visits is resolved to the event id). */
    private const ID_TYPES = [
        Pasien::REFERRAL_TYPE_EMPLOYEE,
        Pasien::REFERRAL_TYPE_DOKTER,
        Pasien::REFERRAL_TYPE_EVENT,
    ];

    private static ?Collection $rules = null;

    /** Column of the visit's referral, falling back to the patient's when the visit has none. */
    public static function ownerSql(string $column): string
    {
        return "(CASE WHEN COALESCE(v.referral_type, '') <> '' THEN v.{$column} ELSE p.{$column} END)";
    }

    public static function typeSql(): string
    {
        return "COALESCE(NULLIF(" . self::ownerSql('referral_type') . ", ''), '" . Pasien::REFERRAL_TYPE_WALK_IN . "')";
    }

    /** Stable key of the referral detail: record id for ID_TYPES, else the lower-cased detail text. */
    public static function keySql(): string
    {
        $type = self::typeSql();
        $id = self::ownerSql('referralable_id');
        $detail = self::ownerSql('referral_detail');
        $idTypes = "'" . implode("','", self::ID_TYPES) . "'";

        return "(CASE
            WHEN {$type} IN ({$idTypes}) AND COALESCE({$id}, '') <> '' THEN {$id}
            WHEN {$type} = '" . Pasien::REFERRAL_TYPE_EVENT . "' THEN COALESCE(
                (SELECT CAST(me.id AS CHAR) FROM marketing_event me WHERE me.kode_event = {$detail} LIMIT 1),
                LOWER(TRIM(COALESCE({$detail}, ''))))
            ELSE LOWER(TRIM(COALESCE({$detail}, '')))
        END)";
    }

    /** "type|key" => is_marketing ('' key = whole type). Empty when the table is missing (not migrated yet). */
    public static function rules(): Collection
    {
        if (self::$rules === null) {
            self::$rules = Schema::hasTable('marketing_referral_mappings')
                ? ReferralMapping::query()->get()->mapWithKeys(fn ($r) => [$r->referral_type . '|' . $r->referral_key => (bool) $r->is_marketing])
                : collect();
        }

        return self::$rules;
    }

    public static function forgetRules(): void
    {
        self::$rules = null;
    }

    public static function isMarketing(string $type, ?string $key): bool
    {
        $rules = self::rules();
        $key = (string) $key;

        if ($key !== '' && in_array($type, self::DETAIL_TYPES, true) && $rules->has($type . '|' . $key)) {
            return $rules[$type . '|' . $key];
        }

        return (bool) ($rules[$type . '|'] ?? false);
    }

    /** Whether any rule marks something as marketing (else the widget asks the admin to map first). */
    public static function hasAnyMarketing(): bool
    {
        return self::rules()->contains(true);
    }

    /**
     * Display names of details: [type => [key => label]] for the given [type, key] pairs
     * (karyawan / dokter / event by id, the rest from the text).
     */
    public static function detailLabels(iterable $pairs): array
    {
        $byType = collect($pairs)->groupBy(fn ($p) => $p[0])->map(fn ($rows) => $rows->pluck(1)->filter(fn ($k) => $k !== '' && $k !== null)->unique()->values());
        $names = [
            Pasien::REFERRAL_TYPE_EMPLOYEE => fn ($ids) => DB::table('hrd_employee')->whereIn('id', $ids)->pluck('nama', 'id'),
            Pasien::REFERRAL_TYPE_DOKTER => fn ($ids) => DB::table('erm_dokters as d')->leftJoin('users as u', 'u.id', '=', 'd.user_id')
                ->whereIn('d.id', $ids)->selectRaw("d.id, COALESCE(NULLIF(TRIM(u.name), ''), CONCAT('Dokter ID ', d.id)) as nama")->pluck('nama', 'id'),
            Pasien::REFERRAL_TYPE_EVENT => fn ($ids) => DB::table('marketing_event')->whereIn('id', array_filter($ids, fn ($k) => ctype_digit((string) $k)))->pluck('nama_event', 'id'),
        ];

        $labels = [];
        foreach ($byType as $type => $keys) {
            $found = isset($names[$type]) && $keys->isNotEmpty() ? $names[$type]($keys->all()) : collect();
            foreach ($keys as $key) {
                $labels[$type][$key] = $found[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
            }
        }

        return $labels;
    }
}
