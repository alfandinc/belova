<?php

namespace Database\Seeders;

use App\Models\HRD\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'CEO',
                'description' => 'Chief Executive Officer',
            ],
            [
                'name' => 'HRD Manager',
                'description' => 'Manajer Sumber Daya Manusia',
            ],
            [
                'name' => 'Manajer Keuangan',
                'description' => 'Manajer Keuangan dan Akuntansi',
            ],
            [
                'name' => 'Manajer Marketing',
                'description' => 'Manajer Pemasaran',
            ],
            [
                'name' => 'Manajer IT',
                'description' => 'Manajer Teknologi Informasi',
            ],
            [
                'name' => 'Staff HRD',
                'description' => 'Staff Sumber Daya Manusia',
            ],
            [
                'name' => 'Staff Keuangan',
                'description' => 'Staff Keuangan dan Akuntansi',
            ],
            [
                'name' => 'Staff Marketing',
                'description' => 'Staff Pemasaran',
            ],
            [
                'name' => 'Programmer',
                'description' => 'Developer/Programmer',
            ],
            [
                'name' => 'Network Administrator',
                'description' => 'Admin Jaringan',
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Layanan Pelanggan',
            ],
            [
                'name' => 'Office Boy',
                'description' => 'Staff Kebersihan',
            ],
            [
                'name' => 'Security',
                'description' => 'Keamanan',
            ],
            [
                'name' => 'Driver',
                'description' => 'Supir',
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
