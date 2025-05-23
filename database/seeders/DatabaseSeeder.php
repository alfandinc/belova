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
        $this->call(DokterSeeder::class);
    }
}
