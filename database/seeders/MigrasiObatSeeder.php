<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiObatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('database/data/migrasiobatfix.csv');

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

            // Sanitasi data numerik: jika kosong, ubah jadi null
            $data = [
                'id' => $row[0],
                'nama' => $row[1] ?? null,
                'satuan' => $row[2] ?? null,
                'dosis' => is_numeric($row[3]) ? $row[3] : null,
                'harga_net' => is_numeric($row[4]) ? $row[4] : null,
                'harga_fornas' => is_numeric($row[5]) ? $row[5] : null,
                'harga_nonfornas' => is_numeric($row[6]) ? $row[6] : null,
                'stok' => is_numeric($row[7]) ? $row[7] : 0,
                'kategori' => $row[8] ?? null,
                'metode_bayar_id' => is_numeric($row[9]) ? $row[9] : null,
                'status_aktif' => is_numeric($row[10]) ? $row[10] : 0,
                'kode_obat' => $row[11] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('erm_obat')->insert($data);
        }

        fclose($file);
    }
}
