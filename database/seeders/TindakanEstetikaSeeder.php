<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TindakanEstetikaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/tindakanestetika.csv');

        if (!file_exists($path)) {
            throw new \Exception("CSV file not found at: $path");
        }

        $file = fopen($path, 'r');

        while (($row = fgetcsv($file)) !== false) {
            $nama = trim($row[0] ?? '');
            $hargaRaw = trim($row[1] ?? '');

            // Normalisasi harga
            $hargaClean = str_replace('.', '', $hargaRaw);       // Hapus titik ribuan
            $hargaNormalized = str_replace(',', '.', $hargaClean); // Ganti koma ke titik desimal

            // Jika kosong atau bukan angka, isi dengan 0.00
            $hargaFinal = is_numeric($hargaNormalized) ? $hargaNormalized : 0.00;

            DB::table('erm_tindakan')->insert([
                'nama'         => $nama,
                'deskripsi'    => null,
                'harga'        => $hargaFinal,
                'spesialis_id' => 6,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        fclose($file);
    }
}
