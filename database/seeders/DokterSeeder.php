<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $dokters = [
            ['id' => 16, 'user_id' => 16, 'spesialisasi_id' => 2, 'klinik_id' => 1],
            ['id' => 145, 'user_id' => 145, 'spesialisasi_id' => 6, 'klinik_id' => 2],
            ['id' => 1158, 'user_id' => 1158, 'spesialisasi_id' => 6, 'klinik_id' => 2],
            ['id' => 1171, 'user_id' => 1171, 'spesialisasi_id' => 6, 'klinik_id' => 2],
            ['id' => 1159, 'user_id' => 1159, 'spesialisasi_id' => 4, 'klinik_id' => 2],
            ['id' => 23, 'user_id' => 23, 'spesialisasi_id' => 5, 'klinik_id' => 1],
            ['id' => 99, 'user_id' => 99, 'spesialisasi_id' => 3, 'klinik_id' => 1],
        ];

        foreach ($dokters as $dokter) {
            DB::table('erm_dokters')->insert([
                'id' => $dokter['id'],
                'user_id' => $dokter['user_id'],
                'spesialisasi_id' => $dokter['spesialisasi_id'],
                'klinik_id' => $dokter['klinik_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
