<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\JasaMedis;

class JasaMedisSeeder extends Seeder
{
    public function run(): void
    {
        // Konsultasi
        JasaMedis::create([
            'nama' => 'Konsultasi Reguler',
            'harga' => 125000,
            'jenis' => 'konsultasi',
        ]);

        JasaMedis::create([
            'nama' => 'Konsultasi Khusus',
            'harga' => 150000,
            'jenis' => 'konsultasi',
        ]);

        // Tindakan untuk Penyakit Dalam
        JasaMedis::insert([
            [
                'nama' => 'EKG',
                'harga' => 120000,
                'jenis' => 'tindakan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'USG Abdomen',
                'harga' => 250000,
                'jenis' => 'tindakan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Pemeriksaan Laboratorium',
                'harga' => 200000,
                'jenis' => 'tindakan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
