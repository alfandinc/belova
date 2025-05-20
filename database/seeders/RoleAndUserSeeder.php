<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat role
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $dokter = Role::firstOrCreate(['name' => 'Dokter']);
        $perawat = Role::firstOrCreate(['name' => 'Perawat']);
        $farmasi = Role::firstOrCreate(['name' => 'Farmasi']);

        // Buat user admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('12345678'),
            ]
        );
        $adminUser->assignRole($admin);

        // Buat user dokter
        $dokterUser = User::firstOrCreate(
            ['email' => 'dokter@example.com'],
            [
                'name' => 'Dokter User',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser->assignRole($dokter);

        // Buat user perawat
        $perawatUser = User::firstOrCreate(
            ['email' => 'perawat@example.com'],
            [
                'name' => 'Perawat User',
                'password' => bcrypt('12345678'),
            ]
        );
        $perawatUser->assignRole($perawat);

        // Buat user farmasi
        $farmasiUser = User::firstOrCreate(
            ['email' => 'farmasi@example.com'],
            [
                'name' => 'Farmasi User',
                'password' => bcrypt('12345678'),
            ]
        );
        $farmasiUser->assignRole($farmasi);
    }
}
