<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\KartuStok;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use App\Models\ERM\GudangMapping;
use App\Services\ERM\StokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillInvoiceStock extends Command
{
    /**
     * The name and signature of the console command.
     * Options:
     *   --invoice_id=  Only process a single invoice
     *   --from=        Start date (YYYY-MM-DD)
     *   --to=          End date (YYYY-MM-DD)
     *   --dry-run      Don't modify DB, just report
     */
    protected $signature = 'stock:backfill-invoices {--invoice_id=} {--from=} {--to=} {--dry-run} {--limit=}
';

    /**
     * The console command description.
     */
    protected $description = 'Backfill kartu stok and reduce stock for fully-paid invoices that were not processed previously';

    public function handle()
    {
        $invoiceId = $this->option('invoice_id');
        $from = $this->option('from');
        $to = $this->option('to');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('Starting backfill process' . ($dryRun ? ' (dry-run)' : ''));

        $query = Invoice::query()
            ->whereColumn('amount_paid', '>=', 'total_amount')
            ->where('total_amount', '>', 0)
            ->orderBy('created_at', 'asc');

        if ($invoiceId) {
            $query->where('id', $invoiceId);
        }
        if ($from && $to) {
            $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        } elseif ($from) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        } elseif ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        if ($limit) {
            $query->limit(intval($limit));
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No fully-paid invoices found for backfill.');
            return 0;
        }

        $stokService = new StokService();

        foreach ($invoices as $invoice) {
            $this->line("Processing invoice {$invoice->id} ({$invoice->invoice_number})...");

            // Skip if there are already kartu stok entries referencing this invoice
            $exists = KartuStok::where('ref_type', 'invoice_penjualan')
                ->where('ref_id', $invoice->id)
                ->exists();

            if ($exists) {
                $this->line(" - Skipped: invoice already has kartu stok entries (ref)");
                continue;
            }

            // Collect reductions per obat_id
            $reduceMap = []; // obat_id => qty

            $items = InvoiceItem::where('invoice_id', $invoice->id)->get();
            foreach ($items as $item) {
                // ResepFarmasi
                if ($item->billable_type === 'App\\Models\\ERM\\ResepFarmasi') {
                    $resep = ResepFarmasi::find($item->billable_id);
                    if ($resep && $resep->obat) {
                        // Skip racikan items (they will be processed grouped) if racikan_ke > 0
                        if ($resep->racikan_ke > 0) continue;
                        $obatId = $resep->obat->id;
                        $qty = intval($item->quantity ?? 1);
                        $reduceMap[$obatId] = ($reduceMap[$obatId] ?? 0) + $qty;
                    }
                }

                // Bundled obat items
                else if ($item->billable_type === 'App\\Models\\ERM\\Obat' && isset($item->description) && str_contains($item->description, 'Obat Bundled:')) {
                    $obat = Obat::find($item->billable_id);
                    if ($obat) {
                        $qty = intval($item->quantity ?? 1);
                        $reduceMap[$obat->id] = ($reduceMap[$obat->id] ?? 0) + $qty;
                    }
                }

                // RiwayatTindakan -> kode tindakan meds (query DB table)
                else if ($item->billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                    $riwayatId = $item->billable_id;
                    $kodeMeds = DB::table('erm_riwayat_tindakan_obat')->where('riwayat_tindakan_id', $riwayatId)->get();
                    foreach ($kodeMeds as $km) {
                        $obatId = $km->obat_id;
                        $qty = intval($km->qty ?? 1);
                        $reduceMap[$obatId] = ($reduceMap[$obatId] ?? 0) + $qty;
                    }
                }
            }

            if (empty($reduceMap)) {
                $this->line(' - No stockable items found on this invoice.');
                continue;
            }

            // Execute reductions per obat using FIFO across batches
            $invoiceSuccess = true;
            DB::beginTransaction();
            try {
                foreach ($reduceMap as $obatId => $qtyNeeded) {
                    $remaining = $qtyNeeded;

                    // Determine gudang: try resep mapping first
                    $gudangId = GudangMapping::getDefaultGudangId('resep') ?: (Gudang::first()->id ?? null);
                    if (!$gudangId) {
                        throw new \Exception('No gudang available');
                    }

                    $stokList = ObatStokGudang::where('obat_id', $obatId)
                        ->where('gudang_id', $gudangId)
                        ->where('stok', '>', 0)
                        ->orderBy('expiration_date', 'asc')
                        ->get();

                    foreach ($stokList as $stok) {
                        if ($remaining <= 0) break;
                        $toReduce = min($remaining, $stok->stok);

                        if ($dryRun) {
                            $this->line("   - [dry-run] Would reduce obat_id={$obatId} batch={$stok->batch} by {$toReduce}");
                        } else {
                            $ok = $stokService->kurangiStok(
                                $obatId,
                                $gudangId,
                                $toReduce,
                                $stok->batch,
                                'invoice_penjualan',
                                $invoice->id,
                                $invoice->invoice_number ? "Penjualan via Invoice: {$invoice->invoice_number}" : 'Penjualan obat'
                            );
                            if (!$ok) {
                                throw new \Exception("Failed to reduce stock for obat_id={$obatId} batch={$stok->batch}");
                            }
                        }

                        $remaining -= $toReduce;
                    }

                    if ($remaining > 0) {
                        throw new \Exception("Insufficient stock for obat_id={$obatId}. Missing {$remaining} units.");
                    }
                }

                if ($dryRun) {
                    DB::rollBack();
                    $this->info(' - Dry run complete for invoice ' . $invoice->id);
                } else {
                    DB::commit();
                    $this->info(' - Stock reductions applied and kartu stok entries created for invoice ' . $invoice->id);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $invoiceSuccess = false;
                $this->error(' - Error processing invoice ' . $invoice->id . ': ' . $e->getMessage());
                Log::error('BackfillInvoiceStock error', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }

            // small pause
            sleep(1);
        }

        $this->info('Backfill process finished.');
        return 0;
    }
}
