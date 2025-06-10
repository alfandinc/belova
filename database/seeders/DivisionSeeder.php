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
                'name' => 'Human Resource',
                'description' => 'Divisi Sumber Daya Manusia',
            ],
            [
                'name' => 'Finance & Accounting',
                'description' => 'Divisi Keuangan dan Akuntansi',
            ],
            [
                'name' => 'Marketing',
                'description' => 'Divisi Pemasaran',
            ],
            [
                'name' => 'Information Technology',
                'description' => 'Divisi Teknologi Informasi',
            ],
            [
                'name' => 'General Affairs',
                'description' => 'Divisi Umum',
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Divisi Layanan Pelanggan',
            ],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }
    }
}
