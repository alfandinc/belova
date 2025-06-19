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
        $path = base_path('database/data/visit20juni.csv');

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

            $pasienId = trim($row[1]);

// Skip jika kosong
if ($pasienId === '' || $pasienId === 'NULL') {
    Log::info("Skipped row $rowNumber: Missing pasien_id", ['row_data' => $row]);
    continue;
}

// Skip jika > 6 digit
if (strlen($pasienId) > 6) {
    Log::info("Skipped row $rowNumber: pasien_id more than 6 digits", ['pasien_id' => $pasienId]);
    continue;
}

// Skip jika tidak ditemukan di erm_pasiens
if (!DB::table('erm_pasiens')->where('id', $pasienId)->exists()) {
    Log::info("Skipped row $rowNumber: pasien_id not found in erm_pasiens", ['pasien_id' => $pasienId]);
    continue;
}


            DB::table('erm_visitations')->insert([
                'id' => $row[0],
                'pasien_id' => $pasienId,
                'metode_bayar_id' => $row[2] == 'NULL' ? 1 : $row[2],
                'dokter_id' => $row[3],

                'status_kunjungan' => $row[8] ?? 0,
                'status_dokumen' => $row[4] == 'NULL' ? null : $row[4],

                'jenis_kunjungan' => 1,
                'tanggal_visitation' => $row[5] ?? null,
                'no_antrian' => $row[6] == 'NULL' ? null : $row[6],
                
                'klinik_id' => $row[7] == 'NULL' ? 1 : $row[7],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
