<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KeluhanUtamaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/keluhanutama.csv');

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

            DB::table('erm_keluhan_utama')->insert([
                'keluhan' => $row[0],
                'spesialisasi_id' => $row[1],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
