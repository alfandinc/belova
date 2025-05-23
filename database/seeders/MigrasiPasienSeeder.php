<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiPasienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/migrasipasien.csv');

        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("CSV file not found or not readable at $path");
        }

        $file = fopen($path, 'r');
        $isHeader = true;

        $logPath = storage_path('logs/migrasi_pasien_skipped.log');
        file_put_contents($logPath, "Skipped rows during patient migration:\n");

        while (($row = fgetcsv($file)) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $id = $row[0];
            $rawNik = strtoupper(trim($row[1] ?? ''));
            $nik = in_array($rawNik, ['', 'NULL', '-']) ? null : $rawNik;

            $nama = $row[2] ?? 'N/A';

            // Check duplicate ID
            if (DB::table('erm_pasiens')->where('id', $id)->exists()) {
                file_put_contents(
                    $logPath,
                    "Skipped: ID '$id' already exists. Name: $nama\n",
                    FILE_APPEND
                );
                continue;
            }

            // Handle tanggal_lahir default
            $tanggalLahir = $row[3] ?? null;
            if (empty($tanggalLahir) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
                $tanggalLahir = '1970-01-01'; // Default fallback
            }

            // Insert valid row
            DB::table('erm_pasiens')->insert([
                'id' => $id,
                'nik' => $nik,
                'nama' => $nama,
                'tanggal_lahir' => $tanggalLahir,
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
