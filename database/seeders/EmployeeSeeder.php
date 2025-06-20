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
            'email' => 'ceo@belova.id',
            'password' => Hash::make('12345678'),
        ]);
        $ceoUser->assignRole('Ceo');

        Employee::create([
            'nama' => 'CEO',
            'position' => 1, // CEO position
            'division_id' => 1, // Typically assigned to main division
            'status' => 'tetap',
            'user_id' => $ceoUser->id,
        ]);

        // 2. Create HRD Manager (directly under CEO)
        $hrdUser = User::create([
            'name' => 'Amadea Dinapramesi, S.Psi',
            'email' => 'dea@belova.id',
            'password' => Hash::make('12345678'),
        ]);
        $hrdUser->assignRole('Hrd');

        Employee::create([
            'nama' => 'Amadea Dinapramesi, S.Psi',
            'position' => 2, // HRD Manager
            'division_id' => 1, // Human Resource
            'user_id' => $hrdUser->id,
        ]);

        // 3. Create Division Managers (under HRD)
        $divisionManagers = [
            [
                'nama' => 'Maya Nunung Alifah, Amd.Kep',
                'email' => 'maya@belova.id',
                'position' => 3, 
                'division_id' => 2, 
            ],
            [
                'nama' => 'Valeria Indah Kurniawati, A.Md',
                'email' => 'vale@belova.id',
                'position' => 4, 
                'division_id' => 3, 
            ],
            [
                'nama' => 'Adi Daryatmo',
                'email' => 'adi@belova.id',
                'position' => 5, 
                'division_id' => 4, 
            ],
            [
                'nama' => 'apt. Noor Hesthisara Hudana Reswar, S.Farm',
                'email' => 'tesa@belova.id',
                'position' => 6, 
                'division_id' => 5, 
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
                'position' => $manager['position'],
                'division_id' => $manager['division_id'],
                'user_id' => $user->id,
            ]);
        }

        // 4. Create Employee
        $divisionEmployees = [
            [
                'nama' => 'Hendri Kurniawati, SE',
                'email' => 'indri@belova.id',
                'position' => 10, 
                'division_id' => 3, 
            ],
            [
                'nama' => 'Yunika Putri Alifah, Amd.Kes',
                'email' => 'putri@belova.id',
                'position' => 9, 
                'division_id' => 2, 
            ],
            [
                'nama' => 'Kristiana Kurnia Sari',
                'email' => 'kristi@belova.id',
                'position' => 10, 
                'division_id' => 3, 
            ],
            [
                'nama' => 'Kunthi Purbaningrum',
                'email' => 'kunthi@belova.id',
                'position' => 9, 
                'division_id' => 2, 
            ],
            [
                'nama' => 'Aida Riris Widaningrum',
                'email' => 'aida@belova.id',
                'position' => 12, 
                'division_id' => 3, 
            ],
            [
                'nama' => 'Tutik Setyoningsih, S.Pd',
                'email' => 'tutik@belova.id',
                'position' => 7, 
                'division_id' => 2, 
            ],
            [
                'nama' => 'Ulya Risky Lestari',
                'email' => 'ulya@belova.id',
                'position' => 8, 
                'division_id' => 2, 
            ],
            [
                'nama' => 'apt. Sofia Uswatun Khasanah, S.Farm',
                'email' => 'sofia@belova.id',
                'position' => 16,
                'division_id' => 5,
            ],
            [
                'nama' => 'M. Alfandi Nurcahyono, Amd. Kom',
                'email' => 'alfandi@belova.id',
                'position' => 11,
                'division_id' => 3,
            ],
            [
                'nama' => 'Dyah Ayu Puspitasari, Amd',
                'email' => 'dyah@belova.id',
                'position' => 17,
                'division_id' => 5,
            ],
            [
                'nama' => 'Vita Ariyani',
                'email' => 'vita@belova.id',
                'position' => 19,
                'division_id' => 5,
            ],
            [
                'nama' => 'Susianti Fita Ningsih',
                'email' => 'tata@belova.id',
                'position' => 9,
                'division_id' => 2,
            ],
            [
                'nama' => 'Halimatus Sakdiyah',
                'email' => 'halim@belova.id',
                'position' => 18,
                'division_id' => 5,
            ],
            [
                'nama' => 'Rasmidi, Amk',
                'email' => 'rasmidi@belova.id',
                'position' => 20,
                'division_id' => 5,
            ],
            [
                'nama' => 'Eko Setiawan',
                'email' => 'eko@belova.id',
                'position' => 12,
                'division_id' => 3,
            ],
            [
                'nama' => 'Tumini',
                'email' => 'tumini@belova.id',
                'position' => 15,
                'division_id' => 4,
            ],
            [
                'nama' => 'Eni Trisnawati',
                'email' => 'eni@belova.id',
                'position' => 15,
                'division_id' => 4,
            ],
            [
                'nama' => 'Dariyah',
                'email' => 'dariyah@belova.id',
                'position' => 15,
                'division_id' => 4,
            ],
            [
                'nama' => 'Habib Romadhon',
                'email' => 'madon@belova.id',
                'position' => 15,
                'division_id' => 4,
            ],
            [
                'nama' => 'Mahmud',
                'email' => 'mahmud@belova.id',
                'position' => 15,
                'division_id' => 4,
            ],       
            
        ];

        foreach ($divisionEmployees as $employee) {
            $user = User::create([
                'name' => $employee['nama'],
                'email' => $employee['email'],
                'password' => Hash::make('12345678'),
            ]);
            $user->assignRole('Employee');

            Employee::create([
                'nama' => $employee['nama'],
                'position' => $employee['position'],
                'division_id' => $employee['division_id'],
                'user_id' => $user->id,
            ]);
        }

        
    }
}
