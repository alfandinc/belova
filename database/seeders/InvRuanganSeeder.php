<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvRuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/inv-Ruangan.csv');

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
            // Ubah 'NULL' string menjadi null
            $row = array_map(function ($value) {
                return strtoupper(trim($value)) === 'NULL' ? null : $value;
            }, $row);

            if (!DB::table('inv_gedung')->where('id', $row[1])->exists()) {
                logger("Skipped: gedung_id {$row[1]} not found");
                continue;
            }

            DB::table('inv_ruangan')->insert([
                'id'                        => $row[0],
                'gedung_id'                 => $row[1],
                'name'                   => $row[2],
                'description'             => $row[3],
                'created_at'              => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
