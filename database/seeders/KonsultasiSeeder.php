<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\Konsultasi;

class KonsultasiSeeder extends Seeder
{
    public function run(): void
    {
        // Konsultasi
        Konsultasi::create([
            'nama' => 'Konsultasi Gratis',
            'harga' => 0,
        ]);
        Konsultasi::create([
            'nama' => 'Konsultasi Reguler',
            'harga' => 125000,
        ]);

        Konsultasi::create([
            'nama' => 'Konsultasi Khusus',
            'harga' => 150000,
        ]);
    }
}
