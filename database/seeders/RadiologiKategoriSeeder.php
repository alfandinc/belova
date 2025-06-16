<?php

namespace Database\Seeders;

use App\Models\ERM\RadiologiKategori;
use Illuminate\Database\Seeder;

class RadiologiKategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'PEMERIKSAAN RONTGEN',
            'ULTRASONOGRAPHY',
            'BMD',
            'MAMMOGRAPHY',
            
        ];

        foreach ($categories as $category) {
            RadiologiKategori::create([
                'nama' => $category,
            ]);
        }
    }
}
