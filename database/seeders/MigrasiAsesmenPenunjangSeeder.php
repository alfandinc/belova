<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiAsesmenPenunjangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/asespenunjang20juni.csv');

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
            if (!DB::table('erm_visitations')->where('id', $row[2])->exists()) {
                logger("Skipped: visitation_id {$row[2]} not found");
                continue;
            }

            DB::table('erm_asesmen_penunjang')->insert([
                'id'                        => $row[0],
                'visitation_id'             => $row[2],
                'diagnosakerja_1'        => $row[3],
                'diagnosakerja_2'        => $row[4],
                'diagnosakerja_3'        => $row[5],
                'diagnosakerja_4'        => $row[6],
                'diagnosakerja_5'        => $row[7],
                'diagnosa_banding'         => $row[8],
                'masalah_medis'            => $row[9],
                'masalah_keperawatan'      => $row[10],
                'sasaran'                  => $row[11],
                'standing_order'           => $row[12],
                'rtl'                      => $row[13],
                               
                'pengantar'                => $row[14],
                'rujuk_ke'                 => $row[15],
                'rujuk_rs'                 => $row[16],
                'rujuk_dokter'             => $row[17],
                'rujuk_puskesmas'          => $row[18],
                'homecare'                 => $row[20],
                'tanggal_homecare'         => $row[21],
                'edukasi_1'                => $row[22],
                'edukasi_2'                => $row[23],
                'edukasi_3'                => $row[24],
                'hubungan_pasien'          => $row[26],
                'alasan'                   => $row[27],

                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
