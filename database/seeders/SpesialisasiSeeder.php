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
            ['nama' => 'Umum'],
            ['nama' => 'Penyakit Dalam'],
            ['nama' => 'Anak'],
        ]);
    }
}
