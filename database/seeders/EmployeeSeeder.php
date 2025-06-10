<?php

namespace Database\Seeders;

use App\Models\HRD\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have required roles
        $roles = ['Ceo', 'Hrd', 'Manager', 'Employee'];
        foreach ($roles as $role) {
            if (!Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
            }
        }

        // 1. Create CEO first (highest in hierarchy)
        $ceoUser = User::create([
            'name' => 'CEO',
            'email' => 'ceo@example.com',
            'password' => Hash::make('12345678'),
        ]);
        $ceoUser->assignRole('Ceo');

        Employee::create([
            'nama' => 'CEO',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1980-01-10',
            'nik' => '1111222233334444',
            'alamat' => 'Jl. Executive No.1, Jakarta',
            'village_id' => null,
            'position' => 1, // CEO position
            'division_id' => 1, // Typically assigned to main division
            'pendidikan' => 'S3 Business Administration',
            'no_hp' => '081122334455',
            'tanggal_masuk' => '2015-01-01',
            'status' => 'tetap',
            'user_id' => $ceoUser->id,
        ]);

        // 2. Create HRD Manager (directly under CEO)
        $hrdUser = User::create([
            'name' => 'HRD Manager',
            'email' => 'hrd@example.com',
            'password' => Hash::make('12345678'),
        ]);
        $hrdUser->assignRole('Hrd');

        Employee::create([
            'nama' => 'HRD Manager',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1985-05-15',
            'nik' => '1234567890123456',
            'alamat' => 'Jl. HR Rasuna Said No.1, Jakarta',
            'village_id' => null, // Update with actual village ID if available
            'position' => 2, // HRD Manager
            'division_id' => 1, // Human Resource
            'pendidikan' => 'S1 Manajemen SDM',
            'no_hp' => '08123456789',
            'tanggal_masuk' => '2018-01-10',
            'status' => 'tetap',
            'user_id' => $hrdUser->id,
        ]);

        // 3. Create Division Managers (under HRD)
        $divisionManagers = [
            [
                'nama' => 'Finance Manager',
                'email' => 'finance.manager@example.com',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1987-08-20',
                'nik' => '2345678901234567',
                'alamat' => 'Jl. Gatot Subroto No. 10, Jakarta',
                'position' => 3, // Manajer Keuangan
                'division_id' => 2, // Finance & Accounting
                'pendidikan' => 'S1 Akuntansi',
            ],
            [
                'nama' => 'Marketing Manager',
                'email' => 'marketing.manager@example.com',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1988-03-12',
                'nik' => '3456789012345678',
                'alamat' => 'Jl. Sudirman No. 25, Jakarta',
                'position' => 4, // Manajer Marketing
                'division_id' => 3, // Marketing
                'pendidikan' => 'S1 Marketing',
            ],
            [
                'nama' => 'IT Manager',
                'email' => 'it.manager@example.com',
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '1986-11-30',
                'nik' => '4567890123456789',
                'alamat' => 'Jl. Kuningan No. 5, Jakarta',
                'position' => 5, // Manajer IT
                'division_id' => 4, // Information Technology
                'pendidikan' => 'S1 Teknik Informatika',
            ],
        ];

        foreach ($divisionManagers as $manager) {
            $user = User::create([
                'name' => $manager['nama'],
                'email' => $manager['email'],
                'password' => Hash::make('12345678'),
            ]);
            $user->assignRole('Manager');

            Employee::create([
                'nama' => $manager['nama'],
                'tempat_lahir' => $manager['tempat_lahir'],
                'tanggal_lahir' => $manager['tanggal_lahir'],
                'nik' => $manager['nik'],
                'alamat' => $manager['alamat'],
                'village_id' => null, // Update if available
                'position' => $manager['position'],
                'division_id' => $manager['division_id'],
                'pendidikan' => $manager['pendidikan'],
                'no_hp' => '08' . rand(100000000, 999999999),
                'tanggal_masuk' => '2019-' . rand(1, 12) . '-' . rand(1, 28),
                'status' => 'tetap',
                'user_id' => $user->id,
            ]);
        }

        // 4. Create regular employees (staff) for each division
        $divisions = [1, 2, 3, 4, 5, 6]; // All division IDs
        $positions = [6, 7, 8, 9, 10, 11, 12, 13, 14]; // Regular staff positions
        $status = ['tetap', 'kontrak', 'tetap', 'kontrak', 'tetap']; // More permanent than contract
        $citys = ['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Makassar', 'Semarang', 'Palembang'];

        // Create 20 regular employees
        for ($i = 1; $i <= 20; $i++) {
            $division = $divisions[array_rand($divisions)];
            $position = $positions[array_rand($positions)];
            $employeeStatus = $status[array_rand($status)];
            $birthDate = rand(1980, 2000) . '-' . rand(1, 12) . '-' . rand(1, 28);
            $startDate = rand(2015, 2023) . '-' . rand(1, 12) . '-' . rand(1, 28);

            // Create some with user accounts, some without
            if ($i % 3 == 0) {
                $user = User::create([
                    'name' => 'Employee ' . $i,
                    'email' => 'employee' . $i . '@example.com',
                    'password' => Hash::make('12345678'),
                ]);
                $user->assignRole('Employee');
                $userId = $user->id;
            } else {
                $userId = null;
            }

            $employee = [
                'nama' => 'Employee ' . $i,
                'tempat_lahir' => $citys[array_rand($citys)],
                'tanggal_lahir' => $birthDate,
                'nik' => '8' . rand(100000000000000, 999999999999999),
                'alamat' => 'Jl. Karyawan No. ' . rand(1, 100) . ', Jakarta',
                'village_id' => null, // Update if available
                'position' => $position,
                'division_id' => $division,
                'pendidikan' => ['SMA', 'D3', 'S1', 'S2'][array_rand(['SMA', 'D3', 'S1', 'S2'])],
                'no_hp' => '08' . rand(100000000, 999999999),
                'tanggal_masuk' => $startDate,
                'status' => $employeeStatus,
                'user_id' => $userId,
            ];

            // Add contract end date for contract employees
            if ($employeeStatus == 'kontrak') {
                $startYear = substr($startDate, 0, 4);
                $endYear = $startYear + rand(1, 3);
                $employee['kontrak_berakhir'] = $endYear . substr($startDate, 4);
            }

            Employee::create($employee);
        }
    }
}
