<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrasiCpptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = base_path('database/data/cppt18juni.csv');

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
            // if (!is_numeric($row[0])) {
            //     logger('Skipped row (id bukan angka):', $row);
            //     continue;
            // }
            if (!DB::table('erm_visitations')->where('id', $row[1])->exists()) {
                logger("Skipped: visitation_id {$row[1]} not found");
                continue;
            }

            DB::table('erm_cppt')->insert([
                // 'id'                        => $row[0],
                'visitation_id'             => $row[1],
                'user_id'                   => $row[2] ? (int)$row[2] : null, 
                'jenis_dokumen'             => $row[3],
                
                's'                         => $row[4],
                'o'                         => $row[5],
                'a'                         => $row[6],
                'p'                         => $row[7],
                'instruksi'                 => $row[8],
                'icd_10'                    => $row[10],
                'dibaca'                    => $row[11],
                'waktu_baca'                => $row[12],
                'handover'                  => $row[13],
                'perawat_handover'          => $row[14],

                'updated_at'                => now(),
            ]);
        }

        fclose($file);
    }
}
