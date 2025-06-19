<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiAsesmenDalamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/asesdalam20juni.csv');

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

            DB::table('erm_asesmen_dalam')->insert([
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
                'obat_dikonsumsi'           => $row[11],
                'keadaan_umum'              => $row[12],
                'td'                        => $row[13],
                'n'                         => $row[14],
                'r'                         => $row[15],
                's'                         => $row[16],
                'e'                         => $row[17],
                'm'                         => $row[18],
                'v'                         => $row[19],
                'hsl'                       => $row[20],
                'kepala'                    => $row[21],
                'leher'                     => $row[22],
                'thorax'                    => $row[23],
                'abdomen'                   => $row[24],
                'genitalia'                 => $row[25],
                'ext_atas'                  => $row[26],
                'ext_bawah'                 => $row[27],
                'ket_status_lokalis'        => $row[28],
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
