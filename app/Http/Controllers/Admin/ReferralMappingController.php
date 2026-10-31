<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Pasien;
use App\Models\Marketing\ReferralMapping;
use App\Support\Marketing\MarketingReferrals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Panel > Referral Marketing: which visit referrals count as Marketing & Sales activity
 * (used by the "Omset Marketing & Sales" dashboard widget). Rules per referral type, with an optional
 * override per detail; see App\Support\Marketing\MarketingReferrals.
 */
class ReferralMappingController extends Controller
{
    public function index()
    {
        $migrated = Schema::hasTable('marketing_referral_mappings');
        $rules = MarketingReferrals::rules();

        // Transacted invoices of the last 12 months per referral, to show what each rule affects
        $usage = DB::table('finance_invoices as fi')
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->whereNotNull('fi.transaction_date')
            ->where('fi.transaction_date', '>=', now()->subYear()->startOfDay())
            ->selectRaw(MarketingReferrals::typeSql() . ' as type, ' . MarketingReferrals::keySql() . ' as ref_key, COUNT(*) as invoices')
            ->groupBy('type', 'ref_key')
            ->get();

        // Details to offer per type: seen in the last 12 months, already given a rule, or a fixed option
        $pairs = $usage->map(fn ($u) => [$u->type, (string) $u->ref_key])
            ->merge($rules->keys()->map(fn ($k) => explode('|', $k, 2)))
            ->merge(collect(Pasien::SOCIAL_MEDIA_REFERRAL_DETAILS)->map(fn ($d) => [Pasien::REFERRAL_TYPE_SOCIAL_MEDIA, $d]))
            ->merge(collect(Pasien::MARKETPLACE_REFERRAL_DETAILS)->map(fn ($d) => [Pasien::REFERRAL_TYPE_MARKETPLACE, $d]))
            ->filter(fn ($p) => ($p[1] ?? '') !== '' && in_array($p[0], MarketingReferrals::DETAIL_TYPES, true));
        $labels = MarketingReferrals::detailLabels($pairs);

        $types = collect(Pasien::referralTypes())->map(function ($type) use ($usage, $rules, $labels) {
            $typeRule = $rules[$type . '|'] ?? null;
            $details = collect($labels[$type] ?? [])
                ->map(function ($label, $key) use ($type, $usage, $rules, $typeRule) {
                    $own = $rules[$type . '|' . $key] ?? null;

                    return (object) [
                        'key' => (string) $key,
                        'label' => $label,
                        'invoices' => (int) $usage->where('type', $type)->where('ref_key', (string) $key)->sum('invoices'),
                        'rule' => $own === null ? 'inherit' : ($own ? '1' : '0'),
                        'effective' => $own ?? (bool) $typeRule,
                    ];
                })
                ->sortBy([['invoices', 'desc'], ['label', 'asc']])
                ->values();

            return (object) [
                'type' => $type,
                'label' => Pasien::referralTypeLabel($type),
                'invoices' => (int) $usage->where('type', $type)->sum('invoices'),
                'is_marketing' => (bool) $typeRule,
                'has_details' => in_array($type, MarketingReferrals::DETAIL_TYPES, true),
                'details' => $details,
                'overrides' => $details->where('rule', '!=', 'inherit')->count(),
            ];
        });

        return view('admin.referral_mapping.index', compact('types', 'migrated'));
    }

    public function update(Request $request)
    {
        abort_unless(Schema::hasTable('marketing_referral_mappings'), 422, 'Tabel mapping belum dibuat: jalankan php artisan migrate.');

        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', Pasien::referralTypes())],
            'key' => ['nullable', 'string', 'max:100'],
            'value' => ['required', 'in:inherit,1,0'],
        ]);
        $key = (string) ($data['key'] ?? '');
        abort_if($key !== '' && !in_array($data['type'], MarketingReferrals::DETAIL_TYPES, true), 422, 'Tipe ini tidak punya pengaturan per detail.');
        abort_if($key === '' && $data['value'] === 'inherit', 422, 'Pilih Ya atau Tidak untuk tipe referral.');

        if ($data['value'] === 'inherit') {
            ReferralMapping::where('referral_type', $data['type'])->where('referral_key', $key)->delete();
        } else {
            ReferralMapping::updateOrCreate(
                ['referral_type' => $data['type'], 'referral_key' => $key],
                ['is_marketing' => $data['value'] === '1', 'updated_by' => Auth::id()]
            );
        }
        MarketingReferrals::forgetRules();

        return response()->json([
            'message' => Pasien::referralTypeLabel($data['type']) . ($key !== '' ? ' · ' . (MarketingReferrals::detailLabels([[$data['type'], $key]])[$data['type']][$key] ?? $key) : '')
                . ' → ' . ['inherit' => 'ikut tipe', '1' => 'dihitung Marketing & Sales', '0' => 'tidak dihitung'][$data['value']],
            'type_is_marketing' => MarketingReferrals::isMarketing($data['type'], ''),
        ]);
    }
}
