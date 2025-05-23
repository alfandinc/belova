<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiPasienSedeer extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
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
            $nik = $row[1] ?? null;

            // If NIK exists and is not unique, skip this row
            if ($nik && DB::table('erm_pasiens')->where('nik', $nik)->exists()) {
                continue;
            }

            DB::table('erm_pasiens')->insert([
                'id' => $row[0],
                'nik' => $row[1] ?? null,
                'nama' => $row[2] ?? null,
                'tanggal_lahir' => $row[3] ?? null,
                'gender' => $row[4] ?? null,
                'alamat' => $row[5] ?? null,
                'no_hp' => $row[6] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($file);
    }
}
