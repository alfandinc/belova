<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpesialisasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        \App\Models\ERM\Spesialisasi::insert([
            ['id' => 1, 'nama' => 'Umum'],
            ['id' => 2, 'nama' => 'Penyakit Dalam'],
            ['id' => 3, 'nama' => 'Anak'],
            ['id' => 4, 'nama' => 'Gigi'],
            ['id' => 5, 'nama' => 'Saraf'],
        ]);
    }
}
