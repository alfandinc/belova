<?php

namespace App\Services\ERM;

use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiPermintaan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap radiologi of a period (for a dashboard widget later): how many of each pemeriksaan and the rupiah they
 * brought, from the invoice item (after discount), counted once the invoice is paid. Includes the radiologi of a
 * paket biaya konsultasi (e.g. "+ USG"), and for the invoices from before the paket created the permintaan, the
 * part set in the paket.
 */
class RadiologiRekapService
{
    public const STATUS_LABELS = [
        'paid' => 'Lunas',
        'partial' => 'Bayar sebagian',
        'issued' => 'Belum dibayar',
        null => 'Belum ada invoice',
    ];

    /** One row per pemeriksaan done: tanggal, pasien, dokter, klinik, pemeriksaan, paket, invoice, nominal, lunas */
    public function rows(string $from, string $to, ?int $klinikId = null, ?int $testId = null): Collection
    {
        return $this->permintaanRows($from, $to, $klinikId, $testId)
            ->concat($this->paketLamaRows($from, $to, $klinikId, $testId))
            ->sortBy('tanggal')->values()
            ->map(function ($r) {
                $r->nominal = round((float) $r->nominal, 2);
                $r->lunas = $r->invoice_status === 'paid';
                $r->invoice_label = self::STATUS_LABELS[$r->invoice_status] ?? $r->invoice_status;
                $r->tanggal = Carbon::parse($r->tanggal)->toDateString();

                return $r;
            });
    }

    /** Per pemeriksaan: jumlah, from a paket konsultasi, rupiah lunas / belum lunas */
    public static function perTest(Collection $rows): Collection
    {
        return $rows->groupBy('pemeriksaan')->map(fn (Collection $g, $nama) => [
            'pemeriksaan' => $nama,
            'jumlah' => $g->count(),
            'jumlah_paket' => $g->where('paket', '!=', null)->count(),
            'lunas' => round($g->where('lunas', true)->sum('nominal'), 2),
            'belum' => round($g->where('lunas', false)->sum('nominal'), 2),
        ])->sortByDesc('lunas')->values();
    }

    /** Every radiologi permintaan of the period: nominal from its invoice item, else (no invoice yet) its billing */
    private function permintaanRows(string $from, string $to, ?int $klinikId, ?int $testId): Collection
    {
        $type = RadiologiPermintaan::class;
        $own = "FROM finance_invoice_items ii JOIN finance_invoices i ON i.id = ii.invoice_id"
            . " WHERE ii.billable_type = ? AND ii.billable_id = p.id ORDER BY FIELD(i.status, 'issued', 'partial', 'paid') DESC, i.id DESC LIMIT 1";
        $paket = Konsultasi::hasRadiologiPaket();

        return DB::table('erm_radiologi_permintaan as p')
            ->join('erm_visitations as v', 'v.id', '=', 'p.visitation_id')
            ->leftJoin('erm_radiologi_test as t', 't.id', '=', 'p.radiologi_test_id')
            ->leftJoin('erm_pasiens as ps', 'ps.id', '=', 'v.pasien_id')
            ->leftJoin('erm_klinik as kl', 'kl.id', '=', 'v.klinik_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as du', 'du.id', '=', 'd.user_id')
            ->when($paket, fn ($q) => $q->leftJoin('erm_konsultasi as k', 'k.id', '=', 'p.konsultasi_id'))
            ->whereBetween(DB::raw('DATE(v.tanggal_visitation)'), [$from, $to])
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId))
            ->when($testId, fn ($q) => $q->where('p.radiologi_test_id', $testId))
            ->select('v.tanggal_visitation as tanggal', 'v.pasien_id', 'ps.nama as pasien', 'du.name as dokter', 'kl.nama as klinik',
                't.nama as pemeriksaan', 'p.status')
            ->selectRaw($paket ? 'k.nama as paket' : 'NULL as paket')
            ->selectRaw("(SELECT i.status {$own}) as invoice_status", [$type])
            ->selectRaw("(SELECT i.invoice_number {$own}) as invoice_number", [$type])
            ->selectRaw("COALESCE((SELECT ii.final_amount {$own}),"
                . " (SELECT b.jumlah FROM finance_billing b WHERE b.billable_type = ? AND b.billable_id = p.id AND b.deleted_at IS NULL ORDER BY b.id LIMIT 1), 0) as nominal",
                [$type, $type])
            ->get();
    }

    /**
     * Invoices of a paket biaya konsultasi from before it created the radiologi permintaan (the radiologi was only
     * inside the konsultasi harga): its part as set in the paket, cut by the item's discount in the same ratio.
     */
    private function paketLamaRows(string $from, string $to, ?int $klinikId, ?int $testId): Collection
    {
        if (!Konsultasi::hasRadiologiPaket()) {
            return collect();
        }

        return DB::table('finance_invoice_items as ii')
            ->join('finance_invoices as i', 'i.id', '=', 'ii.invoice_id')
            ->join('erm_konsultasi_radiologi as kr', 'kr.konsultasi_id', '=', 'ii.billable_id')
            ->join('erm_konsultasi as k', 'k.id', '=', 'kr.konsultasi_id')
            ->join('erm_radiologi_test as t', 't.id', '=', 'kr.radiologi_test_id')
            ->join('erm_visitations as v', 'v.id', '=', 'i.visitation_id')
            ->leftJoin('erm_pasiens as ps', 'ps.id', '=', 'v.pasien_id')
            ->leftJoin('erm_klinik as kl', 'kl.id', '=', 'v.klinik_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as du', 'du.id', '=', 'd.user_id')
            ->where('ii.billable_type', Konsultasi::class)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('erm_radiologi_permintaan as p')
                ->whereColumn('p.visitation_id', 'i.visitation_id')->whereColumn('p.konsultasi_id', 'ii.billable_id'))
            ->whereBetween(DB::raw('DATE(v.tanggal_visitation)'), [$from, $to])
            ->when($klinikId, fn ($q) => $q->where('v.klinik_id', $klinikId))
            ->when($testId, fn ($q) => $q->where('kr.radiologi_test_id', $testId))
            ->select('v.tanggal_visitation as tanggal', 'v.pasien_id', 'ps.nama as pasien', 'du.name as dokter', 'kl.nama as klinik',
                't.nama as pemeriksaan', 'k.nama as paket', 'i.status as invoice_status', 'i.invoice_number')
            ->selectRaw("'paket_lama' as status")
            ->selectRaw('kr.harga * ii.quantity * (CASE WHEN ii.unit_price * ii.quantity > 0'
                . ' THEN LEAST(1, ii.final_amount / (ii.unit_price * ii.quantity)) ELSE 0 END) as nominal')
            ->get();
    }
}
