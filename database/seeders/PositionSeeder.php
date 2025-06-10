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
                'division_id' => 1,
            ],
            [
                'name' => 'HRD Manager',
                'description' => 'Manajer Sumber Daya Manusia',
                'division_id' => 1,
            ],
            [
                'name' => 'Manajer Keuangan',
                'description' => 'Manajer Keuangan dan Akuntansi',
                'division_id' => 2,
            ],
            [
                'name' => 'Manajer Marketing',
                'description' => 'Manajer Pemasaran',
                'division_id' => 3,
            ],
            [
                'name' => 'Manajer IT',
                'description' => 'Manajer Teknologi Informasi',
                'division_id' => 4,
            ],
            [
                'name' => 'Staff HRD',
                'description' => 'Staff Sumber Daya Manusia',
                'division_id' => 1,
            ],
            [
                'name' => 'Staff Keuangan',
                'description' => 'Staff Keuangan dan Akuntansi',
                'division_id' => 2,
            ],
            [
                'name' => 'Staff Marketing',
                'description' => 'Staff Pemasaran',
                'division_id' => 3,
            ],
            [
                'name' => 'Programmer',
                'description' => 'Developer/Programmer',
                'division_id' => 4,
            ],
            [
                'name' => 'Network Administrator',
                'description' => 'Admin Jaringan',
                'division_id' => 4,
            ],
            [
                'name' => 'Customer Service',
                'description' => 'Layanan Pelanggan',
                'division_id' => 6,
            ],
            [
                'name' => 'Office Boy',
                'description' => 'Staff Kebersihan',
                'division_id' => 5,
            ],
            [
                'name' => 'Security',
                'description' => 'Keamanan',
                'division_id' => 5,
            ],
            [
                'name' => 'Driver',
                'description' => 'Supir',
                'division_id' => 5,
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
