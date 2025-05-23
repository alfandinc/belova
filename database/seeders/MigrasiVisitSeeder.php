<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/migrasivisit.csv');

        // Open the file and read each line
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');
        $isHeader = true;

        while (($row = fgetcsv($file)) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue; // skip header
            }

            $pasienId = $row[1] ?? null;

            // Skip if pasien_id is null or doesn't exist in the pasiens table
            if (!$pasienId || !DB::table('pasiens')->where('id', $pasienId)->exists()) {
                continue;
            }

            DB::table('erm_visitations')->insert([
                'id' => $row[0],
                'pasien_id' => $pasienId,
                'metode_bayar_id' => $row[2] == 'NULL' ? 1 : $row[2],
                'dokter_id' => 1,
                'user_id' => 7,
                'progress' => 3,
                'status_dokumen' => $row[5] ?? null,
                'tanggal_visitation' => $row[6] ?? null,
                'no_antrian' => $row[7] == 'NULL' ? 1 : $row[7],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
