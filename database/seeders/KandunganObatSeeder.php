<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KandunganObatSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('database/data/kandunganobat.csv');

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

            $obatId = trim($row[2] ?? '');
            $zatAktifId = trim($row[3] ?? '');

            // Skip if either value is empty or invalid
            if (
                $obatId === '' ||
                $zatAktifId === '' ||
                strtolower($zatAktifId) === 'null'
            ) {
                continue;
            }

            // Check if both IDs exist in their respective tables
            $obatExists = DB::table('erm_obat')->where('id', $obatId)->exists();
            // $zatAktifExists = DB::table('erm_zataktif')->where('id', $zatAktifId)->exists();

            if (!$obatExists) {
                continue;
            }

            DB::table('erm_kandungan_obat')->insert([
                'obat_id' => $obatId,
                'zataktif_id' => $zatAktifId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
