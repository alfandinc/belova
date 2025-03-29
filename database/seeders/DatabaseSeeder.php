<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'pendaftaran']);
        Role::create(['name' => 'dokter']);
        Role::create(['name' => 'perawat']);

        // Assign a role to an existing user (for testing)
        $user = User::first();
        if ($user) {
            $user->assignRole('dokter');
        }
    }
}
