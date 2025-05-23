<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('database/data/migrasiobat.csv');

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

            DB::table('erm_obat')->insert([
                'id' => $row[0],
                'nama' => $row[1] ?? null,
                'dosis' => 100,
                'satuan' => $row[3] ?? null,
                'harga_net' => $row[4] == 'NULL' ? 1 : $row[4],
                'harga_nonfornas' => $row[5] == 'NULL' ? 1 : $row[5],
                'kategori' => $row[6] ?? null,
                'metode_bayar_id' => 1,
                'stok' => 100,
                'status_aktif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
