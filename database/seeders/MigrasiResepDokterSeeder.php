<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiResepDokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/migrasiresepdokter.csv');

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');
        $isHeader = true;

        while (($row = fgetcsv($file)) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $nullIfStringNull = fn($val) => ($val === 'NULL' || $val === null || $val === '') ? null : $val;

            if (!DB::table('erm_visitations')->where('id', $row[1])->exists()) {
                logger("Skipped: visitation_id {$row[1]} not found");
                continue;
            }
            if (!DB::table('erm_obat')->where('id', $row[2])->exists()) {
                logger("Skipped: obat_id {$row[2]} not found");
                continue;
            }

            DB::table('erm_resepdokter')->insert([
                'id'             => $row[0],
                'visitation_id'  => $nullIfStringNull($row[1]),
                'obat_id'        => $nullIfStringNull($row[2]),
                'jumlah'         => $nullIfStringNull($row[3]),
                'dosis'          => $nullIfStringNull($row[4]),
                'bungkus'        => $nullIfStringNull($row[5]),
                'racikan_ke'     => $nullIfStringNull($row[6]),
                'aturan_pakai'   => $nullIfStringNull($row[7]),
                'wadah'          => $nullIfStringNull($row[8]),
                'user_id'        => $nullIfStringNull($row[10]),
                'created_at'     => $nullIfStringNull($row[9]) ?? now(),
                'updated_at'     => now(),
            ]);
        }

        fclose($file);
    }
}
