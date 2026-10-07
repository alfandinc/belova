<?php
namespace App\Console\Commands;

use App\Models\ERM\Obat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-time fix for master obat prices. Definition: HPP = WITHOUT PPN, HNA = WITH PPN.
 * Old data had them the other way round (HPP = HNA x 1.11, because faktur approval put PPN into HPP).
 *
 *  1. Obat with an approved faktur: HPP = latest faktur harga per unit (before diskon, excl. tax),
 *     HNA = HPP x (1 + PPN). Same as FakturBeliController::approveFaktur / StokService now.
 *  2. Otherwise, when HPP = HNA x (1 + PPN) (old pattern): swap the two values.
 *  3. Anything else is left unchanged and counted in the report.
 *
 * Safe to run again: after a fix neither rule produces a change. Dry run by default (summary + CSV);
 * use --apply to update erm_obat. Past invoices are not touched.
 */
class RecalculateObatHpp extends Command
{
    protected $signature = 'obat:recalc-hpp {--apply : Update erm_obat.hpp / hna (otherwise preview only)}';

    protected $description = 'Fix master obat HPP (excl. PPN) and HNA (incl. PPN) from faktur beli / old swapped values';

    public function handle()
    {
        $factor = 1 + Obat::PPN_PERCENT / 100;

        // Latest received item per obat from approved faktur (received_date, then newest faktur/item)
        $latest = DB::table('erm_fakturbeli_items as i')
            ->join('erm_fakturbeli as f', 'f.id', '=', 'i.fakturbeli_id')
            ->whereIn('f.status', ['diapprove', 'diretur'])
            ->where('i.qty', '>', 0)
            ->where('i.harga', '>', 0) // bonus/free lines carry no price
            ->whereNotNull('i.obat_id')
            ->orderByRaw('COALESCE(f.received_date, f.created_at) DESC')
            ->orderByDesc('f.id')
            ->orderByDesc('i.id')
            ->get(['i.obat_id', 'i.harga', 'f.no_faktur'])
            ->unique('obat_id')
            ->keyBy('obat_id');

        $obats = DB::table('erm_obat')->orderBy('id')->get(['id', 'kode_obat', 'nama', 'hpp', 'hna']);

        $changes = [];
        $counts = ['faktur' => 0, 'tukar' => 0, 'sudah_benar' => 0, 'tidak_diubah' => 0];
        foreach ($obats as $obat) {
            $oldHpp = $obat->hpp === null ? null : round((float) $obat->hpp, 2);
            $oldHna = $obat->hna === null ? null : round((float) $obat->hna, 2);

            if (isset($latest[$obat->id])) {
                $source = 'faktur ' . $latest[$obat->id]->no_faktur;
                $newHpp = round((float) $latest[$obat->id]->harga, 2);
                $newHna = Obat::hnaFromHpp($newHpp);
                $rule = 'faktur';
            } elseif ($oldHpp > 0 && $oldHna > 0 && abs($oldHna - $oldHpp * $factor) < 0.01 + $oldHpp * 0.0005) {
                $counts['sudah_benar']++; // already HNA = HPP + PPN
                continue;
            } elseif ($oldHpp > 0 && $oldHna > 0 && abs($oldHpp - $oldHna * $factor) < 0.01 + $oldHna * 0.0005) {
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
                'hpp_lama' => $oldHpp,
                'hpp_baru' => $newHpp,
                'hna_lama' => $oldHna,
                'hna_baru' => $newHna,
                'sumber' => $source,
            ];
        }

        $this->info('Total obat: ' . $obats->count() . ' | PPN: ' . Obat::PPN_PERCENT . '%');
        $this->info("Diubah dari faktur: {$counts['faktur']} | ditukar (data lama): {$counts['tukar']} | sudah benar: {$counts['sudah_benar']} | tidak diubah (tanpa faktur & pola tidak cocok): {$counts['tidak_diubah']}");

        if (!empty($changes)) {
            $this->table(
                ['ID', 'Kode', 'Nama', 'HPP lama', 'HPP baru', 'HNA lama', 'HNA baru', 'Sumber'],
                array_map(fn ($c) => [$c['obat_id'], $c['kode_obat'], mb_strimwidth((string) $c['nama'], 0, 30, '…'), $c['hpp_lama'], $c['hpp_baru'], $c['hna_lama'], $c['hna_baru'], mb_strimwidth($c['sumber'], 0, 28, '…')], array_slice($changes, 0, 15))
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
                    'hpp' => $c['hpp_baru'],
                    'hna' => $c['hna_baru'],
                    'updated_at' => now(),
                ]);
            }
        });
        Log::info('obat:recalc-hpp applied', ['updated' => count($changes)]);
        $this->info('HPP/HNA diperbarui untuk ' . count($changes) . ' obat.');

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
