<?php

namespace Database\Seeders;

use App\Models\HRD\Division;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'id' => 1,
                'name' => 'Human Resource',
                'description' => 'Divisi Sumber Daya Manusia',
            ],
            [
                'id' => 2,
                'name' => 'Administrasi dan Urusan Bisnis',
                'description' => 'Divisi Administrasi dan Urusan Bisnis',
            ],
            [
                'id' => 3,
                'name' => 'Pemasaran dan Hubungan Digital',
                'description' => 'Divisi Pemasaran dan Hubungan Digital',
            ],
            [
                'id' => 4,
                'name' => 'Operasianal dan Pengelolaan Fasilitas',
                'description' => 'Divisi Operasianal dan Pengelolaan Fasilitas',
            ],
            [
                'id' => 5,
                'name' => 'Kefarmasian dan Asuransi',
                'description' => 'Divisi Kefarmasian dan Asuransi',
            ],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }
    }
}
