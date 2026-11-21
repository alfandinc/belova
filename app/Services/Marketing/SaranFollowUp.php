<?php

namespace App\Services\Marketing;

use App\Models\ERM\Visitation;
use App\Models\Marketing\FollowUp;
use App\Services\ERM\AlurPelayanan;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "Perlu Follow Up": pasien the CRM should contact, derived from visit data (nothing stored).
 *
 * One row per pasien + klinik, from the pasien's LAST visit at that klinik (a later visit, even one only
 * registered, means the pasien came back / is coming, so no row). The trigger visit must be transacted
 * (paid, piutang, asuransi, or a processed Rp 0 bill):
 *  - konsultasi: a konsultasi without any tindakan, 3–14 days ago
 *  - produk:     a product purchase 30–60 days ago (jenis Beli Produk, or obat/resep at PRODUK_RESEP_KLINIKS)
 *  - lama:       any other last visit 90–365 days ago
 * A row disappears once the pasien has a follow up at that klinik made after the trigger visit
 * (at most FOLLOW_UP_COOLDOWN_DAYS ago, so an old unanswered one lets the pasien come back to the list).
 */
class SaranFollowUp
{
    public const ALASAN = [
        'konsultasi' => ['label' => 'Konsultasi tanpa perawatan', 'color' => 'success', 'from' => 3, 'to' => 14],
        'produk' => ['label' => 'Produk mungkin habis', 'color' => 'primary', 'from' => 30, 'to' => 60],
        'lama' => ['label' => 'Lama tidak datang', 'color' => 'secondary', 'from' => 90, 'to' => 365],
    ];

    /** Kategori suggested for the follow up, first one the klinik offers (FollowUp::kategoriOfferedFor). */
    private const KATEGORI = [
        'konsultasi' => ['Konsultasi', 'Perawatan'],
        'produk' => ['Produk'],
        'lama' => ['Perawatan', 'Konsultasi'],
    ];

    /** Kliniks where obat sold through a resep is skincare, so it counts as a product purchase (2 = Belova Skin). */
    public const PRODUK_RESEP_KLINIKS = [2];

    public const FOLLOW_UP_COOLDOWN_DAYS = 30;

    /**
     * Rows: pasien_id, klinik_id, alasan, trigger_visit_id, trigger_date, jenis_kunjungan,
     * pasien_nama, no_hp, klinik_nama, klinik_color, klinik_logo.
     */
    public static function query(?string $alasan = null, $klinikId = null): Builder
    {
        $parts = [];
        foreach (array_keys(self::ALASAN) as $key) {
            if (!$alasan || $alasan === $key) {
                $parts[] = self::alasanQuery($key);
            }
        }
        $union = array_shift($parts);
        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        $cooldown = now()->subDays(self::FOLLOW_UP_COOLDOWN_DAYS)->startOfDay()->toDateTimeString();

        return DB::query()->fromSub($union, 's')
            ->join('erm_pasiens as p', 'p.id', '=', 's.pasien_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 's.klinik_id')
            ->select('s.*', 'p.nama as pasien_nama', 'p.no_hp', 'k.nama as klinik_nama', 'k.color as klinik_color', 'k.logo as klinik_logo')
            ->when($klinikId, fn ($q) => $q->where('s.klinik_id', $klinikId))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('marketing_follow_ups as f')
                ->whereColumn('f.pasien_id', 's.pasien_id')
                ->where(fn ($k) => $k->whereNull('f.klinik_id')->orWhereColumn('f.klinik_id', 's.klinik_id'))
                ->whereRaw('f.created_at >= GREATEST(s.trigger_date, ?)', [$cooldown]));
    }

    /** Count per alasan (klinik filter applied). */
    public static function counts($klinikId = null): array
    {
        $rows = self::query(null, $klinikId)
            ->select('s.alasan')->selectRaw('COUNT(*) as jumlah')->groupBy('s.alasan')->pluck('jumlah', 'alasan');

        return array_map(fn ($key) => (int) ($rows[$key] ?? 0), array_combine(array_keys(self::ALASAN), array_keys(self::ALASAN)));
    }

    /** Kategori to preselect in the follow up form, or null when the klinik offers none of them. */
    public static function kategoriFor(string $alasan, $klinikId): ?string
    {
        foreach (self::KATEGORI[$alasan] ?? [] as $kategori) {
            if (FollowUp::kategoriOfferedFor($kategori, $klinikId)) {
                return $kategori;
            }
        }

        return null;
    }

    /** One line why the pasien is listed, e.g. "Konsultasi 5 hari lalu, tanpa perawatan". */
    public static function keterangan(string $alasan, string $triggerDate, $jenis): string
    {
        $days = (int) \Carbon\Carbon::parse($triggerDate)->startOfDay()->diffInDays(now()->startOfDay());
        $lalu = $days >= 60 ? floor($days / 30) . ' bulan lalu' : $days . ' hari lalu';

        return match ($alasan) {
            'konsultasi' => "Konsultasi {$lalu}, tanpa perawatan",
            'produk' => "Beli produk {$lalu}, belum datang lagi",
            default => 'Terakhir datang ' . $lalu . ' (' . Visitation::typeLabel($jenis) . ')',
        };
    }

    /** Last transacted visits at their klinik that match one alasan. */
    private static function alasanQuery(string $key): Builder
    {
        $rule = self::ALASAN[$key];
        $today = now()->startOfDay();

        $q = DB::table('erm_visitations as v')
            ->selectRaw('v.pasien_id, v.klinik_id, ? as alasan, v.id as trigger_visit_id, v.tanggal_visitation as trigger_date, v.jenis_kunjungan', [$key])
            ->whereNotNull('v.pasien_id')
            ->whereNotNull('v.klinik_id')
            ->where(fn ($s) => $s->whereNull('v.status_kunjungan')->orWhere('v.status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN))
            ->whereBetween('v.tanggal_visitation', [
                $today->copy()->subDays($rule['to'])->toDateString(),
                $today->copy()->subDays($rule['from'])->toDateString(),
            ])
            // Transacted (same rule as Alur Pelayanan / Billing "Belum Transaksi")
            ->whereExists(fn ($s) => $s->selectRaw('1')->from('finance_invoices as fi')
                ->whereColumn('fi.visitation_id', 'v.id')
                ->where(fn ($c) => $c->whereNull('fi.status')->orWhere('fi.status', '!=', 'canceled'))
                ->whereRaw(AlurPelayanan::invoiceTransactedSql('fi')))
            // The pasien's last visit at this klinik (a later one, even only registered, ends the suggestion)
            ->whereNotExists(fn ($s) => $s->selectRaw('1')->from('erm_visitations as lv')
                ->whereColumn('lv.pasien_id', 'v.pasien_id')
                ->whereColumn('lv.klinik_id', 'v.klinik_id')
                ->where(fn ($c) => $c->whereNull('lv.status_kunjungan')->orWhere('lv.status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN))
                ->where(fn ($c) => $c->whereColumn('lv.tanggal_visitation', '>', 'v.tanggal_visitation')
                    ->orWhere(fn ($t) => $t->whereColumn('lv.tanggal_visitation', 'v.tanggal_visitation')->whereColumn('lv.id', '>', 'v.id'))));

        return match ($key) {
            'konsultasi' => $q->where('v.jenis_kunjungan', Visitation::TYPE_KONSULTASI)
                ->whereNotExists(fn ($s) => $s->selectRaw('1')->from('erm_riwayat_tindakan as rt')->whereColumn('rt.visitation_id', 'v.id')),
            'produk' => $q->where(fn ($p) => $p->where('v.jenis_kunjungan', Visitation::TYPE_PRODUK)
                ->orWhere(fn ($r) => $r->whereIn('v.klinik_id', self::PRODUK_RESEP_KLINIKS)
                    ->whereExists(fn ($s) => $s->selectRaw('1')->from('finance_invoices as pfi')
                        ->join('finance_invoice_items as pii', 'pii.invoice_id', '=', 'pfi.id')
                        ->whereColumn('pfi.visitation_id', 'v.id')
                        ->whereIn('pii.billable_type', [\App\Models\ERM\Obat::class, \App\Models\ERM\ResepFarmasi::class])))),
            // Marketplace / Reseller buyers are not clinic patients to win back
            default => $q->whereNotIn('v.jenis_kunjungan', [Visitation::TYPE_MARKETPLACE, Visitation::TYPE_RESELLER]),
        };
    }
}
