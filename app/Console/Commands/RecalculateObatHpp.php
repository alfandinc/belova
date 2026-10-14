<?php
namespace App\Console\Commands;

use App\Models\ERM\Obat;
use App\Services\ERM\HnaFakturService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fix for master obat prices. Definition: HNA = harga beli per unit + ongkir share, WITHOUT PPN;
 * HPP = HNA + PPN. Data before this rule had them the other way round (HPP = without PPN, HNA = HPP x 1.11).
 *
 *  1. Obat with an approved faktur: HNA = latest faktur harga per unit (before diskon, excl. tax) + its share
 *     of that faktur's ongkir, HPP = HNA x (1 + PPN). Same as FakturBeliController::approveFaktur / StokService.
 *  2. Otherwise, when HNA = HPP x (1 + PPN) (old pattern): swap the two values.
 *  3. Anything else is left unchanged and counted in the report.
 *
 * Safe to run again: after a fix neither rule produces a change. Dry run by default (summary + CSV);
 * use --apply to update erm_obat. Past invoices are not touched.
 */
class RecalculateObatHpp extends Command
{
    protected $signature = 'obat:recalc-hpp {--apply : Update erm_obat.hpp / hna (otherwise preview only)}';

    protected $description = 'Fix master obat HNA (excl. PPN, + ongkir) and HPP (HNA + PPN) from faktur beli / old swapped values';

    public function handle()
    {
        $factor = 1 + Obat::PPN_PERCENT / 100;
        $hasOngkir = Schema::hasColumn('erm_fakturbeli', 'ongkir');

        // Latest approved faktur line per obat, with its HNA (harga + ongkir share)
        $latest = app(HnaFakturService::class)->terakhir();

        $obats = DB::table('erm_obat')->orderBy('id')->get(['id', 'kode_obat', 'nama', 'hpp', 'hna']);

        $changes = [];
        $counts = ['faktur' => 0, 'tukar' => 0, 'sudah_benar' => 0, 'tidak_diubah' => 0];
        foreach ($obats as $obat) {
            $oldHpp = $obat->hpp === null ? null : round((float) $obat->hpp, 2);
            $oldHna = $obat->hna === null ? null : round((float) $obat->hna, 2);

            if (isset($latest[$obat->id])) {
                $row = $latest[$obat->id];
                $source = 'faktur ' . $row->no_faktur;
                $newHna = $row->hna;
                $newHpp = Obat::hppFromHna($newHna);
                $rule = 'faktur';
            } elseif ($oldHpp > 0 && $oldHna > 0 && abs($oldHpp - $oldHna * $factor) < 0.01 + $oldHna * 0.0005) {
                $counts['sudah_benar']++; // already HPP = HNA + PPN
                continue;
            } elseif ($oldHpp > 0 && $oldHna > 0 && abs($oldHna - $oldHpp * $factor) < 0.01 + $oldHpp * 0.0005) {
                $source = 'tukar HPP/HNA lama';
                $newHpp = $oldHna;
                $newHna = $oldHpp;
                $rule = 'tukar';
            } else {
                $counts['tidak_diubah']++;
                continue;
            }

            if ($this->same($oldHpp, $newHpp) && $this->same($oldHna, $newHna)) {
                $counts['sudah_benar']++;
                continue;
            }

            $counts[$rule]++;
            $changes[] = [
                'obat_id' => $obat->id,
                'kode_obat' => $obat->kode_obat,
                'nama' => $obat->nama,
                'hna_lama' => $oldHna,
                'hna_baru' => $newHna,
                'hpp_lama' => $oldHpp,
                'hpp_baru' => $newHpp,
                'sumber' => $source,
            ];
        }

        $this->info('Total obat: ' . $obats->count() . ' | PPN: ' . Obat::PPN_PERCENT . '%' . ($hasOngkir ? '' : ' | kolom ongkir belum ada (jalankan migrate)'));
        $this->info("Diubah dari faktur: {$counts['faktur']} | ditukar (data lama): {$counts['tukar']} | sudah benar: {$counts['sudah_benar']} | tidak diubah (tanpa faktur & pola tidak cocok): {$counts['tidak_diubah']}");

        if (!empty($changes)) {
            $this->table(
                ['ID', 'Kode', 'Nama', 'HNA lama', 'HNA baru', 'HPP lama', 'HPP baru', 'Sumber'],
                array_map(fn ($c) => [$c['obat_id'], $c['kode_obat'], mb_strimwidth((string) $c['nama'], 0, 30, '…'), $c['hna_lama'], $c['hna_baru'], $c['hpp_lama'], $c['hpp_baru'], mb_strimwidth($c['sumber'], 0, 28, '…')], array_slice($changes, 0, 15))
            );

            $path = storage_path('app/hpp-hna-recalc-' . now()->format('Ymd-His') . '.csv');
            $fh = fopen($path, 'w');
            fputcsv($fh, array_keys($changes[0]));
            foreach ($changes as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
            $this->info("Daftar lengkap: {$path}");
        }

        if (!$this->option('apply')) {
            $this->warn('Preview saja. Jalankan dengan --apply untuk menyimpan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                DB::table('erm_obat')->where('id', $c['obat_id'])->update([
                    'hna' => $c['hna_baru'],
                    'hpp' => $c['hpp_baru'],
                    'updated_at' => now(),
                ]);
            }
        });
        Log::info('obat:recalc-hpp applied', ['updated' => count($changes)]);
        $this->info('HNA/HPP diperbarui untuk ' . count($changes) . ' obat.');

        return self::SUCCESS;
    }

    private function same(?float $a, ?float $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return abs($a - $b) < 0.005;
    }
}
