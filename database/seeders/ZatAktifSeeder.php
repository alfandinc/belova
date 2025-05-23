<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZatAktifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = base_path('database/data/zataktif.csv');

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

            $nama = trim($row[1]);

            // Skip if 'nama' already exists in the table
            if (DB::table('erm_zataktif')->where('nama', $nama)->exists()) {
                continue;
            }

            DB::table('erm_zataktif')->insert([
                'id' => $row[0],
                'nama' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
