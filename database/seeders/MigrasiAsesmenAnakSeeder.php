<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiAsesmenAnakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/asesanak18juni.csv');

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

            // Validasi id dan visitation_id adalah angka
            if (!is_numeric($row[0])) {
                logger('Skipped row (id bukan angka):', $row);
                continue;
            }
            if (!DB::table('erm_visitations')->where('id', $row[1])->exists()) {
                logger("Skipped: visitation_id {$row[1]} not found");
                continue;
            }

            DB::table('erm_asesmen_anak')->insert([
                'id'                        => $row[0],
                'visitation_id'             => $row[1],
                'autoanamnesis'             => $row[2],
                'alloanamnesis'             => $row[3],
                'anamnesis1'                => $row[4],
                'anamnesis2'                => $row[5],
                'keluhan_utama'             => $row[6],
                'riwayat_penyakit_sekarang' => $row[7],
                'allo_dengan'               => $row[8],
                'hasil_allo'                => $row[9],
                'riwayat_penyakit_dahulu'   => $row[10],
                'riwayat_penyakit_keluarga' => $row[12],
                'riwayat_kehamilan'         => $row[13],

                'riwayat_makanan'           => $row[14],
                'riwayat_tumbang'           => $row[15],
                'imunisasi_dasar'           => $row[18],
                'imunisasi_dasar_ket'       => $row[16],
                'imunisasi_lanjut'          => $row[19],
                'imunisasi_lanjut_ket'      => $row[17],

                'keadaan_umum'              => $row[20],

                'td'                        => $row[21],
                'n'                         => $row[22],
                'r'                         => $row[23],
                's'                         => $row[24],
                'gizi'                      => $row[25],
                'bb'                        => $row[27],
                'tb'                        => $row[28],
                'lk'                        => $row[29],
                'kepala'                    => $row[30],
                'leher'                     => $row[31],
                'thorax'                    => $row[32],
                'jantung'                   => $row[33],
                'paru'                      => $row[34],
                'abdomen'                   => $row[35],
                'genitalia'                 => $row[36],
                'extremitas'                => $row[37],
                'pemeriksaan_fisik_tambahan' => $row[38],


            
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
