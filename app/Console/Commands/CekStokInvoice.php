<?php

namespace App\Console\Commands;

use App\Models\ERM\GudangMapping;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\GudangExpiredService;
use App\Services\ERM\StokService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Invoices whose obat were not (fully) cut from stock: per obat, what the billing needs vs what kartu stok shows
 * for the invoice (invoice_penjualan keluar − invoice_return masuk), with the likely cause.
 * Read-only by default; --apply cuts the missing qty of processed invoices (ref invoice_penjualan, so a later
 * "Terima Pembayaran" counts it as already cut). Meant to be run on production.
 */
class CekStokInvoice extends Command
{
    protected $signature = 'stok:cek-invoice
        {--from= : Tanggal invoice mulai (YYYY-MM-DD), default 30 hari lalu}
        {--to= : Tanggal invoice sampai (YYYY-MM-DD), default hari ini}
        {--invoice= : Hanya satu nomor / id invoice}
        {--apply : Potong stok yang kurang (tanpa ini hanya laporan)}
        {--path= : Lokasi file CSV (default storage/app/cek_stok_invoice_YYYYmmdd_His.csv)}';

    protected $description = 'Laporan (dan koreksi dengan --apply) obat invoice yang tidak / kurang terpotong dari stok';

    /** Transaction type of each source for the gudang mapping (same as billing). */
    private const MAPPING_TYPE = ['resep' => 'resep', 'resep racikan' => 'resep', 'obat bundled' => 'tindakan', 'kode tindakan' => 'kode_tindakan', 'lab' => 'lab'];

    public function handle(): int
    {
        $from = $this->option('from') ?: now()->subDays(30)->toDateString();
        $to = $this->option('to') ?: now()->toDateString();
        $apply = (bool) $this->option('apply');

        // Processed invoices, plus invoices older than today never processed at all ("Terima Pembayaran" not
        // clicked, e.g. a zero-total multi-visit session): stock is only cut when the payment is processed
        $invoices = DB::table('finance_invoices')
            ->where(fn ($q) => $q->where(fn ($p) => $p->whereNotNull('payment_method')->where('payment_method', '!=', ''))
                ->orWhere('created_at', '<', now()->startOfDay()))
            ->when($this->option('invoice'), fn ($q, $v) => $q->where(fn ($w) => $w->where('invoice_number', $v)->orWhere('id', $v)),
                fn ($q) => $q->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']))
            ->orderBy('id')
            ->get(['id', 'invoice_number', 'visitation_id', 'status', 'payment_method', 'created_at']);

        $this->info("Memeriksa {$invoices->count()} invoice" . ($apply ? ' (MODE APPLY: stok yang kurang akan dipotong)' : ' (hanya laporan)') . '...');

        $rows = [];
        $applied = ['invoice' => 0, 'obat' => 0, 'gagal' => 0];
        foreach ($invoices as $inv) {
            $processed = !empty($inv->payment_method);
            $cut = $this->alreadyCut($inv->id);
            $firstCut = DB::table('erm_kartu_stok')->where('ref_type', 'invoice_penjualan')->where('ref_id', $inv->id)->min('created_at');

            $missingForApply = [];
            foreach ($this->required($inv->visitation_id) as $obatId => $need) {
                $missing = round($need['qty'] - ($cut[$obatId] ?? 0), 4);
                if ($missing <= 0.0001) {
                    continue;
                }
                $reason = !$processed
                    ? 'invoice belum diproses (Terima Pembayaran belum diklik)'
                    : $this->reason($need, empty($cut), $firstCut);
                $rows[] = [
                    'invoice' => $inv->invoice_number,
                    'invoice_id' => $inv->id,
                    'tanggal' => $inv->created_at,
                    'status' => $inv->status . ' / ' . ($inv->payment_method ?: '-'),
                    'sumber' => implode(', ', array_unique($need['sumber'])),
                    'obat_id' => $obatId,
                    'obat' => $need['nama'],
                    'dibutuhkan' => $need['qty'],
                    'sudah_dipotong' => $cut[$obatId] ?? 0,
                    'kurang' => $missing,
                    'kemungkinan_sebab' => $reason,
                    'koreksi' => '',
                ];
                if ($processed) {
                    $missingForApply[$obatId] = ['qty' => $missing, 'type' => self::MAPPING_TYPE[$need['sumber'][0]] ?? 'resep', 'row' => count($rows) - 1];
                }
            }

            if ($apply && $missingForApply) {
                $applied['invoice']++;
                foreach ($missingForApply as $obatId => $m) {
                    try {
                        $gudang = $this->cutMissing($inv, $obatId, $m['qty'], $m['type']);
                        $rows[$m['row']]['koreksi'] = "dipotong dari gudang {$gudang}";
                        $applied['obat']++;
                    } catch (\Throwable $e) {
                        $rows[$m['row']]['koreksi'] = 'GAGAL: ' . $e->getMessage();
                        $applied['gagal']++;
                    }
                }
            }
        }

        if (!$rows) {
            $this->info('Tidak ada obat yang kurang terpotong. Semua invoice sudah sesuai kartu stok.');
            return self::SUCCESS;
        }

        $path = $this->option('path') ?: storage_path('app/cek_stok_invoice_' . now()->format('Ymd_His') . '.csv');
        $fh = fopen($path, 'w');
        fputcsv($fh, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);

        $this->table(['Sebab', 'Jumlah obat'], collect($rows)->countBy('kemungkinan_sebab')->sortDesc()->map(fn ($n, $s) => [$s, $n])->values()->all());
        $this->table(array_keys($rows[0]), array_slice($rows, 0, 30));
        if (count($rows) > 30) {
            $this->line('... ' . (count($rows) - 30) . ' baris lagi di file CSV.');
        }
        if ($apply) {
            $this->info("Koreksi: {$applied['obat']} obat dipotong di {$applied['invoice']} invoice, {$applied['gagal']} gagal (lihat kolom koreksi).");
        } else {
            $this->line('Untuk memotong stok yang kurang (invoice yang sudah diproses saja), jalankan lagi dengan --apply.');
        }
        $this->info('File CSV: ' . $path);

        return self::SUCCESS;
    }

    /** Net qty per obat kartu stok already shows for the invoice. */
    private function alreadyCut(int $invoiceId): array
    {
        return DB::table('erm_kartu_stok')->where('ref_id', $invoiceId)
            ->where(fn ($q) => $q->where(fn ($k) => $k->where('ref_type', 'invoice_penjualan')->where('tipe', 'keluar'))
                ->orWhere(fn ($m) => $m->where('ref_type', 'invoice_return')->where('tipe', 'masuk')))
            ->get(['obat_id', 'ref_type', 'qty'])
            ->groupBy('obat_id')
            ->map(fn ($rows) => round($rows->sum(fn ($r) => $r->ref_type === 'invoice_penjualan' ? (float) $r->qty : -(float) $r->qty), 4))
            ->all();
    }

    /**
     * What the billing of the visit cuts, per obat (same quantities as BillingController): resep (billing qty),
     * racikan (jumlah_racikan, else bungkus), obat bundled (billing qty), kode tindakan (qty), lab (dosis x qty).
     */
    private function required(string $visitationId): array
    {
        $need = [];
        $add = function ($obatId, $qty, $sumber, $nama, $aktif, $billingAt) use (&$need) {
            if ((float) $qty <= 0 || !$obatId) {
                return;
            }
            $need[$obatId] ??= ['qty' => 0.0, 'sumber' => [], 'nama' => $nama, 'aktif' => true, 'billing_at' => $billingAt];
            $need[$obatId]['qty'] = round($need[$obatId]['qty'] + (float) $qty, 4);
            $need[$obatId]['sumber'][] = $sumber;
            $need[$obatId]['aktif'] = $need[$obatId]['aktif'] && $aktif;
            $need[$obatId]['billing_at'] = max($need[$obatId]['billing_at'], $billingAt);
        };
        $base = fn () => DB::table('finance_billing as b')->where('b.visitation_id', $visitationId)->whereNull('b.deleted_at');

        // Resep (soft-deleted resep are skipped by billing too)
        foreach ($base()->join('erm_resepfarmasi as r', 'r.id', '=', 'b.billable_id')->leftJoin('erm_obat as o', 'o.id', '=', 'r.obat_id')
            ->where('b.billable_type', 'App\\Models\\ERM\\ResepFarmasi')->whereNull('r.deleted_at')
            ->get(['b.created_at', 'b.qty', 'r.obat_id', 'r.racikan_ke', 'r.jumlah_racikan', 'r.bungkus', 'o.nama', 'o.status_aktif']) as $r) {
            $racikan = (int) $r->racikan_ke > 0;
            $qty = $racikan ? ((float) $r->jumlah_racikan > 0 ? $r->jumlah_racikan : ($r->bungkus ?: $r->qty)) : ($r->qty ?? 1);
            $add($r->obat_id, $qty, $racikan ? 'resep racikan' : 'resep', $r->nama, (int) $r->status_aktif === 1, $r->created_at);
        }

        foreach ($base()->leftJoin('erm_obat as o', 'o.id', '=', 'b.billable_id')
            ->where('b.billable_type', 'App\\Models\\ERM\\Obat')->where('b.keterangan', 'like', '%Obat Bundled:%')
            ->get(['b.created_at', 'b.qty', 'b.billable_id as obat_id', 'o.nama', 'o.status_aktif']) as $r) {
            $add($r->obat_id, $r->qty ?? 1, 'obat bundled', $r->nama, (int) $r->status_aktif === 1, $r->created_at);
        }

        foreach ($base()->join('erm_riwayat_tindakan_obat as rto', 'rto.riwayat_tindakan_id', '=', 'b.billable_id')->leftJoin('erm_obat as o', 'o.id', '=', 'rto.obat_id')
            ->where('b.billable_type', 'App\\Models\\ERM\\RiwayatTindakan')
            ->get(['b.created_at', 'rto.qty', 'rto.obat_id', 'o.nama', 'o.status_aktif']) as $r) {
            $add($r->obat_id, $r->qty ?? 1, 'kode tindakan', $r->nama, (int) $r->status_aktif === 1, $r->created_at);
        }

        // Lab: LabPermintaan (its test x billing qty), LabTest added at the kasir (x billing qty), LabPaket (its tests x 1)
        $labTests = collect()
            ->concat($base()->join('erm_lab_permintaan as lp', 'lp.id', '=', 'b.billable_id')->where('b.billable_type', 'App\\Models\\ERM\\LabPermintaan')
                ->get(['b.created_at', 'b.qty', 'lp.lab_test_id']))
            ->concat($base()->where('b.billable_type', 'App\\Models\\ERM\\LabTest')->get(['b.created_at', 'b.qty', 'b.billable_id as lab_test_id']))
            ->concat($base()->join('erm_lab_permintaan as lp', fn ($j) => $j->on('lp.lab_paket_id', '=', 'b.billable_id')->on('lp.visitation_id', '=', 'b.visitation_id'))
                ->where('b.billable_type', 'App\\Models\\ERM\\LabPaket')->get(['b.created_at', DB::raw('1 as qty'), 'lp.lab_test_id']));
        foreach ($labTests as $t) {
            $qtyTest = (float) ($t->qty ?: 1);
            if ($qtyTest >= 100 && $qtyTest <= 1000) {
                $qtyTest /= 100; // same normalisation as BillingController::labObatRequirementsForBilling
            }
            foreach (DB::table('erm_lab_test_obat as lto')->leftJoin('erm_obat as o', 'o.id', '=', 'lto.obat_id')
                ->where('lto.lab_test_id', $t->lab_test_id)->where('lto.dosis', '>', 0)
                ->get(['lto.obat_id', 'lto.dosis', 'o.nama', 'o.status_aktif']) as $o) {
                $add($o->obat_id, (float) $o->dosis * $qtyTest, 'lab', $o->nama, (int) $o->status_aktif === 1, $t->created_at);
            }
        }

        return $need;
    }

    private function reason(array $need, bool $noKartu, ?string $firstCut): string
    {
        return match (true) {
            !$need['aktif'] => 'obat nonaktif (dulu dilewati diam-diam) - sudah diperbaiki',
            $firstCut !== null && $need['billing_at'] > $firstCut => 'ditambahkan setelah pembayaran pertama (dulu tidak dipotong) - sudah diperbaiki',
            $noKartu => 'invoice sama sekali tanpa kartu stok',
            in_array('resep racikan', $need['sumber'], true) => 'racikan gagal dipotong tanpa error (stok kurang / gudang) - sudah diperbaiki',
            in_array('lab', $need['sumber'], true) => 'lab (mis. ditambah di kasir / gudang kurang stok) - sudah diperbaiki',
            in_array('kode tindakan', $need['sumber'], true) => 'obat tindakan kurang terpotong (gudang baris tindakan) - sudah diperbaiki',
            default => 'lainnya (cek manual)',
        };
    }

    /**
     * Cut the missing qty FEFO (same order as billing) from the mapped gudang, or the gudang with the most stok
     * when the mapped one lacks it. Returns the gudang name; throws when no gudang has enough.
     */
    private function cutMissing(object $inv, int $obatId, float $qty, string $type): string
    {
        $expired = app(GudangExpiredService::class);
        $stokIn = fn ($g) => (float) ObatStokGudang::where('obat_id', $obatId)->where('gudang_id', $g)->where('stok', '>', 0)->sum('stok');

        $mapping = GudangMapping::resolveGudangForTransaction($type);
        $gudangId = $mapping->gudang_id ?? null;
        if (!$gudangId || $stokIn($gudangId) < $qty) {
            $gudangId = ObatStokGudang::where('obat_id', $obatId)->where('stok', '>', 0)
                ->select('gudang_id', DB::raw('SUM(stok) as total'))->groupBy('gudang_id')->orderByDesc('total')->get()
                ->first(fn ($g) => !$expired->isGudangExpired($g->gudang_id) && (float) $g->total >= $qty)
                ?->gudang_id;
        }
        if (!$gudangId) {
            throw new \RuntimeException("tidak ada gudang dengan stok cukup ({$qty})");
        }

        DB::transaction(function () use ($inv, $obatId, $qty, $gudangId) {
            $remaining = $qty;
            $batches = ObatStokGudang::where('obat_id', $obatId)->where('gudang_id', $gudangId)->where('stok', '>', 0)
                ->orderByRaw(ObatStokGudang::sqlSudahEd())->urutFefo()->get();
            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, (float) $batch->stok);
                (new StokService())->kurangiStok($obatId, $gudangId, $take, $batch->batch, 'invoice_penjualan', $inv->id,
                    "Koreksi stok terlewat Invoice: {$inv->invoice_number}");
                $remaining -= $take;
            }
            if ($remaining > 0.0001) {
                throw new \RuntimeException("stok kurang {$remaining}");
            }
        });

        return (string) (DB::table('erm_gudang')->where('id', $gudangId)->value('nama') ?? $gudangId);
    }
}
