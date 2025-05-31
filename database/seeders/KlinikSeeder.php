<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\Klinik;

class KlinikSeeder extends Seeder
{
    public function run(): void
    {
        // Tindakan untuk Penyakit Dalam
        Klinik::insert([
            [
                'id' => 1,
                'nama' => 'Klinik Utama Premiere Belova',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nama' => 'Klinik Pratama Belova Skin & Beauty Center',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
