<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZatAktifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert data into the erm_zataktif table
        DB::table('erm_zataktif')->insert([
            ['id' => 9, 'nama' => 'Zat Aktif 1'],
            ['id' => 10, 'nama' => 'Zat Aktif 2'],
            ['id' => 11, 'nama' => 'Zat Aktif 3'],

        ]);
    }
}
