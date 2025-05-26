<?php

namespace Database\Seeders;

use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Pasien;
use App\Models\ERM\ResepDokter;
use App\Models\ERM\ZatAktif;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(Icd10Seeder::class);
        $this->call(AreaSeeder::class);
        $this->call(MetodeBayarSeeder::class);
        $this->call(RoleAndUserSeeder::class);
        $this->call(SpesialisasiSeeder::class);
        $this->call(DokterSeeder::class);
        $this->call(ZatAktifSeeder::class);
        $this->call(MigrasiObatSeeder::class);
        $this->call(MigrasiPasienSeeder::class);
        $this->call(MigrasiVisitSeeder::class);
        $this->call(MigrasiResepDokterSeeder::class);
        $this->call(MigrasiResepFarmasiSeeder::class);
    }
}
