<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiAsesmenPerawatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/asesperawat20juni.csv');

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

            // Validasi id adalah angka
            if (!is_numeric($row[0])) {
                logger('Skipped row (id bukan angka):', $row);
                continue;
            }
            if (!DB::table('erm_visitations')->where('id', $row[1])->exists()) {
                logger("Skipped: visitation_id {$row[1]} not found");
                continue;
            }

            DB::table('erm_asesmen_perawats')->insert([
                'id'                        => $row[0],
                'visitation_id'             => $row[1],
                'keluhan_utama'             => $row[3],
                'alasan_kunjungan'          => $row[4],
                'kesadaran'                 => $row[5],
                'td'                        => $row[6],
                'nadi'                      => $row[7],
                'rr'                        => $row[8],
                'suhu'                      => $row[9],
                'riwayat_psikososial'       => $row[10],
                'tb'                        => $row[12],
                'bb'                        => $row[13],
                'lla'                       => $row[14],
                'diet'                      => $row[15],
                'porsi'                     => $row[16],
                'imt'                       => $row[17],
                'presentase'                => $row[18],
                'efek'                      => $row[19],
                'nyeri'                     => $row[20],
                'p'                         => $row[25],
                'q'                         => $row[26],
                'r'                         => $row[27],
                't'                         => $row[28],
                'onset'                     => $row[24],
                'skor'                      => $row[30],
                'kategori'                  => $row[24],
                'kategori_risja'            => $row[29],
                'status_fungsional'         => $row[31],
                'status_fungsional_keterangan' => $row[32],


                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
