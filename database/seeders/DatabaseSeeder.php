<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Panggil seeder tambahan
        $this->call(RoleAndUserSeeder::class);
        $this->call(SpesialisasiSeeder::class);
    }
}
