<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrasiVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/migrasivisit.csv');

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');
        $isHeader = true;
        $rowNumber = 1;

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;

            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            // Skip if pasien_id is empty
            if (trim($row[1]) === '' || $row[1] === 'NULL') {
                Log::info("Skipped row $rowNumber: Missing pasien_id", ['row_data' => $row]);
                continue;
            }

            DB::table('erm_visitations')->insert([
                'id' => $row[0],
                'pasien_id' => $row[1],
                'metode_bayar_id' => $row[2] == 'NULL' ? 1 : $row[2],
                'dokter_id' => $row[3],
                'status_kunjungan' => $row[7] ?? 0,
                'status_dokumen' => $row[4] == 'NULL' ? null : $row[4],
                'tanggal_visitation' => $row[5] ?? null,
                'no_antrian' => $row[6] == 'NULL' ? 1 : $row[6],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
