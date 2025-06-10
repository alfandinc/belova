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
        $pendaftaran = Role::firstOrCreate(['name' => 'Pendaftaran']);
        $kasir = Role::firstOrCreate(['name' => 'Kasir']);
        $marketing = Role::firstOrCreate(['name' => 'Marketing']);

        // Buat user admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@belova.com'],
            [
                'id' => 1,
                'name' => 'Admin User',
                'password' => bcrypt('12345678'),
            ]
        );

        // Buat user marketing
        $adminUser = User::firstOrCreate(
            ['email' => 'marketing@belova.com'],
            [
                'id' => 7,
                'name' => 'Marketin User',
                'password' => bcrypt('12345678'),
            ]
        );
        $adminUser->assignRole($admin);
        $adminUser->assignRole($admin);

        // Buat user dokter
        $dokterUser = User::firstOrCreate(
            ['email' => 'dokter@belova.com'],
            [
                'id' => 2,
                'name' => 'Dokter User',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser->assignRole($dokter);

        // Buat user perawat
        $perawatUser = User::firstOrCreate(
            ['email' => 'perawat@belova.com'],
            [
                'id' => 3,
                'name' => 'Perawat User',
                'password' => bcrypt('12345678'),
            ]
        );
        $perawatUser->assignRole($perawat);

        // Buat user farmasi
        $farmasiUser = User::firstOrCreate(
            ['email' => 'farmasi@belova.com'],
            [
                'id' => 4,
                'name' => 'Farmasi User',
                'password' => bcrypt('12345678'),
            ]
        );
        $farmasiUser->assignRole($farmasi);

        // Buat user pendaftaran
        $pendaftaranUser = User::firstOrCreate(
            ['email' => 'pendaftaran@belova.com'],
            [
                'id' => 5,
                'name' => 'Pendaftaran User',
                'password' => bcrypt('12345678'),
            ]
        );
        $pendaftaranUser->assignRole($pendaftaran);


        // Buat user Kasir
        $kasirUser = User::firstOrCreate(
            ['email' => 'kasir@belova.com'],
            [
                'id' => 6,
                'name' => 'Kasir User',
                'password' => bcrypt('12345678'),
            ]
        );
        $kasirUser->assignRole($kasir);

        // PENYAKIT DALAM
        $dokterUser1 = User::firstOrCreate(
            ['email' => 'wahyuaji@belova.com'],
            [
                'id' => 16,
                'name' => 'dr. Wahyu Aji Wibowo, Dr. MSc., Sp.PD, FINASIM.',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser1->assignRole($dokter);

        // UMUM
        $dokterUser2 = User::firstOrCreate(
            ['email' => 'andhikaputri@belova.com'],
            [
                'id' => 145,
                'name' => 'dr. Andhika Putri Perdana, M. Biomed (AAM)',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser2->assignRole($dokter);

        $dokterUser3 = User::firstOrCreate(
            ['email' => 'dynar@belova.com'],
            [
                'id' => 1158,
                'name' => 'dr. Dynar Amelya Fanthony',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser3->assignRole($dokter);

        $dokterUser4 = User::firstOrCreate(
            ['email' => 'qholfi@belova.com'],
            [
                'id' => 1171,
                'name' => 'dr. Qholfi Anggi Uraini Sahid',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser4->assignRole($dokter);

        // GIGI
        $dokterUser5 = User::firstOrCreate(
            ['email' => 'andhita@belova.com'],
            [
                'id' => 1159,
                'name' => 'drg. Andhita Permatasari',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser5->assignRole($dokter);

        // SARAF
        $dokterUser6 = User::firstOrCreate(
            ['email' => 'erupsiana@belova.com'],
            [
                'id' => 23,
                'name' => 'dr. Erupsiana Fitri Indrihapsari,dr Sp.N',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser6->assignRole($dokter);

        // ANAK
        $dokterUser7 = User::firstOrCreate(
            ['email' => 'ariehapsari@belova.com'],
            [
                'id' => 99,
                'name' => 'dr. Arie Hapsari I.K., Sp.A',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser7->assignRole($dokter);
    }
}
