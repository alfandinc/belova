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
        $path = base_path('database/data/migrasipasien.csv');

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
                'nama' => $row[2] ?? null,
                'satuan' => $row[2] ?? null,
                'dosis' => $row[2] ?? null,
                'harga_fornas' => $row[2] ?? null,
                'harga_nonfornas' => $row[2] ?? null,
                'status_aktif' => 1,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
