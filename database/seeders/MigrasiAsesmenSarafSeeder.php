<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiAsesmenSarafSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/asessaraf18juni.csv');

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

            DB::table('erm_asesmen_saraf')->insert([
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
                'kepala'                    => $row[12],
                'leher'                     => $row[13],
                'thorax'                    => $row[14],
                'abdomen'                   => $row[15],
                'genitalia'                 => $row[16],
                'ext_atas'                  => $row[17],
                'ext_bawah'                 => $row[18],
                'keadaan_umum'              => $row[19],
                'td'                        => $row[20],
                'n'                         => $row[21],
                'r'                         => $row[22],
                's'                         => $row[23],
                'e'                         => $row[24],
                'm'                         => $row[25],
                'v'                         => $row[26],
                'hsl'                       => $row[27],
                'vas'                       => $row[28],
                'diameter_ket'              => $row[29],
                'diameter_1'                => $row[30],
                'diameter_2'                => $row[31],
                'isokor'                    => $row[32],
                'anisokor'                  => $row[33],
                'reflek_cahaya'             => $row[34],
                'reflek_cahaya1'            => $row[35],
                'reflek_cahaya2'            => $row[36],
                'reflek_cornea'             => $row[37],
                'reflek_cornea1'            => $row[38],
                'reflek_cornea2'            => $row[39],
                'nervus'                    => $row[40],
                'kaku_kuduk'                => $row[41],
                'sign'                      => $row[42],
                'brudzinki'                 => $row[43],
                'kernig'                    => $row[44],
                'doll'                      => $row[45],
                'phenomena'                 => $row[46],
                'vertebra'                  => $row[47],
                'extremitas'                => $row[48],
                'gerak1'                    => $row[49],
                'gerak2'                    => $row[50],
                'gerak3'                    => $row[51],
                'gerak4'                    => $row[52],
                'reflek_fisio1'          => $row[53],
                'reflek_fisio2'          => $row[54],
                'reflek_fisio3'          => $row[55],
                'reflek_fisio4'          => $row[56],
                'reflek_pato1'           => $row[57],
                'reflek_pato2'           => $row[58],
                'reflek_pato3'           => $row[59],
                'reflek_pato4'           => $row[60],
                'add_tambahan'              => $row[61],
                'clonus'                    => $row[62],
                'sensibilitas'             => $row[63],

                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
