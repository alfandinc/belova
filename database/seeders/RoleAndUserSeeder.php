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
            ['email' => 'admin@belova.id'],
            [
                'id' => 1,
                'name' => 'Admin User',
                'password' => bcrypt('12345678'),
            ]
        );
        $adminUser->assignRole($admin);
        $adminUser->assignRole($admin);

        // Buat user marketing
        $marketingUser = User::firstOrCreate(
            ['email' => 'marketing@belova.id'],
            [
                'id' => 7,
                'name' => 'Marketin User',
                'password' => bcrypt('12345678'),
            ]
        );
        $marketingUser->assignRole($marketing);
        $marketingUser->assignRole($marketing);

        // Buat user dokter
        $dokterUser = User::firstOrCreate(
            ['email' => 'dokter@belova.id'],
            [
                'id' => 2,
                'name' => 'Dokter User',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser->assignRole($dokter);

        // Buat user perawat
        $perawatUser = User::firstOrCreate(
            ['email' => 'perawat@belova.id'],
            [
                'id' => 3,
                'name' => 'Perawat User',
                'password' => bcrypt('12345678'),
            ]
        );
        $perawatUser->assignRole($perawat);

        // Buat user farmasi
        $farmasiUser = User::firstOrCreate(
            ['email' => 'farmasi@belova.id'],
            [
                'id' => 4,
                'name' => 'Farmasi User',
                'password' => bcrypt('12345678'),
            ]
        );
        $farmasiUser->assignRole($farmasi);

        // Buat user pendaftaran
        $pendaftaranUser = User::firstOrCreate(
            ['email' => 'pendaftaran@belova.id'],
            [
                'id' => 5,
                'name' => 'Pendaftaran User',
                'password' => bcrypt('12345678'),
            ]
        );
        $pendaftaranUser->assignRole($pendaftaran);


        // Buat user Kasir
        $kasirUser = User::firstOrCreate(
            ['email' => 'kasir@belova.id'],
            [
                'id' => 6,
                'name' => 'Kasir User',
                'password' => bcrypt('12345678'),
            ]
        );
        $kasirUser->assignRole($kasir);

        // PENYAKIT DALAM
        $dokterUser1 = User::firstOrCreate(
            ['email' => 'wahyuaji@belova.id'],
            [
                'id' => 16,
                'name' => 'dr. Wahyu Aji Wibowo, Dr. MSc., Sp.PD, FINASIM.',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser1->assignRole($dokter);

        // UMUM
        $dokterUser2 = User::firstOrCreate(
            ['email' => 'andhikaputri@belova.id'],
            [
                'id' => 145,
                'name' => 'dr. Andhika Putri Perdana, M. Biomed (AAM)',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser2->assignRole($dokter);

        $dokterUser3 = User::firstOrCreate(
            ['email' => 'dynar@belova.id'],
            [
                'id' => 1158,
                'name' => 'dr. Dynar Amelya Fanthony',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser3->assignRole($dokter);

        $dokterUser4 = User::firstOrCreate(
            ['email' => 'qholfi@belova.id'],
            [
                'id' => 1171,
                'name' => 'dr. Qholfi Anggi Uraini Sahid',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser4->assignRole($dokter);

        // GIGI
        $dokterUser5 = User::firstOrCreate(
            ['email' => 'andhita@belova.id'],
            [
                'id' => 1159,
                'name' => 'drg. Andhita Permatasari',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser5->assignRole($dokter);

        // SARAF
        $dokterUser6 = User::firstOrCreate(
            ['email' => 'erupsiana@belova.id'],
            [
                'id' => 23,
                'name' => 'dr. Erupsiana Fitri Indrihapsari,dr Sp.N',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser6->assignRole($dokter);

        // ANAK
        $dokterUser7 = User::firstOrCreate(
            ['email' => 'ariehapsari@belova.id'],
            [
                'id' => 99,
                'name' => 'dr. Arie Hapsari I.K., Sp.A',
                'password' => bcrypt('12345678'),
            ]
        );
        $dokterUser7->assignRole($dokter);
    }
}
