<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ERM\LabKategori;

class LabKategoriSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'HEMATOLOGI',
            'HEMOSTASIS',
            'URINALISA',
            'FAESES',
            'FAAL HATI',
            'PENCERNAAN',
            'MOLEKULER',
            'SITOLOGI & ANALISIS LAIN',
            'LEMAK',
            'DIABETES MELLITUS',
            'ELEKTROLIT',
            'HEPATITIS',
            'BONE MAKER',
            'PREPARAT',
            'KULTUR & SENSITIVITAS',
            'FUNGSI TIROID',
            'TORCH',
            'ALERGI',
            'AUTOIMUN'
        ];

        foreach ($categories as $category) {
            LabKategori::create([
                'nama' => $category,
            ]);
        }
    }
}