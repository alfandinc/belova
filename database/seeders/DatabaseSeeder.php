<?php

namespace Database\Seeders;

use App\Models\ERM\Pasien;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Panggil seeder tambahan
        // $this->call(RoleAndUserSeeder::class);
        // $this->call(SpesialisasiSeeder::class);
        // $this->call(VisitationSeeder::class);
        // $this->call(PositionSeeder::class);
        $this->call(Icd10Seeder::class,);
    }
}
