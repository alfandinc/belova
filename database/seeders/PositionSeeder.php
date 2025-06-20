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
                'id' => 1,
                'name' => 'CEO',
                'description' => 'Chief Executive Officer',
                'division_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'HRD',
                'description' => 'Manajer Sumber Daya Manusia',
                'division_id' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Manajer Administrasi dan Urusan Bisnis',
                'description' => 'Manajer Administrasi dan Urusan Bisnis',
                'division_id' => 2,
            ],
            [
                'id' => 4,
                'name' => 'Manajer Pemasaran dan Hubungan Digital',
                'description' => 'Manajer Pemasaran dan Hubungan Digital',
                'division_id' => 3,
            ],
            [
                'id' => 5,
                'name' => 'Manajer Operasianal dan Pengelolaan Fasilitas',
                'description' => 'Manajer Operasianal dan Pengelolaan Fasilitas',
                'division_id' => 4,
            ],
            [
                'id' => 6,
                'name' => 'Manajer Kefarmasian dan Asuransi',
                'description' => 'Manajer Kefarmasian dan Asuransi',
                'division_id' => 5,
            ],
            [
                'id' => 7,
                'name' => 'Staff Keuangan',
                'description' => 'Staff Keuangan dan Akuntansi',
                'division_id' => 2,
            ],
            [
                'id' => 8,
                'name' => 'Kasir',
                'description' => 'Staff Keuangan dan Akuntansi',
                'division_id' => 2,
            ],
            [
                'id' => 9,
                'name' => 'Beautician',
                'description' => 'Staff Kecantikan',
                'division_id' => 2,
            ],
            [
                'id' => 10,
                'name' => 'Staff Marketing',
                'description' => 'Staff Pemasaran',
                'division_id' => 3,
            ],
            [
                'id' => 11,
                'name' => 'IT Supervisor',
                'description' => 'Developer/Programmer',
                'division_id' => 3,
            ],
    
            [
                'id' => 12,
                'name' => 'Front Office',
                'description' => 'Layanan Pelanggan',
                'division_id' => 3,
            ],
            [
                'id' => 13,
                'name' => 'Admin Sosial Media',
                'description' => 'Layanan Pelanggan',
                'division_id' => 3,
            ],
            [
                'id' => 14,
                'name' => 'Grafis Desainer',
                'description' => 'Layanan Pelanggan',
                'division_id' => 3,
            ],
            [
                'id' => 15,
                'name' => 'Staff Kebersihan',
                'description' => 'Layanan Kebersihan',
                'division_id' => 4,
            ],
            [
                'id' => 16,
                'name' => 'Apoteker',
                'description' => 'Apoteker',
                'division_id' => 5,
            ],
            [
                'id' => 17,
                'name' => 'Asisten Apoteker',
                'description' => 'Asisten Apoteker',
                'division_id' => 5,
            ],
            [
                'id' => 18,
                'name' => 'Admin Gudang Farmasi',
                'description' => 'Admin Gudang Farmasi',
                'division_id' => 5,
            ],
            [
                'id' => 19,
                'name' => 'Staff Kefarmasian',
                'description' => 'Staff Kefarmasian',
                'division_id' => 5,
            ],
            [
                'id' => 20,
                'name' => 'Perawat',
                'description' => 'Perawat',
                'division_id' => 5,
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
