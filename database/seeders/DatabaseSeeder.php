<?php

namespace Database\Seeders;

use App\Models\ERM\Konsultasi;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // $this->call(Icd10Seeder::class);
        // $this->call(AreaSeeder::class);
        // $this->call(MetodeBayarSeeder::class);
        // $this->call(RoleAndUserSeeder::class);
        // $this->call(SpesialisasiSeeder::class);
        // $this->call(KlinikSeeder::class);
        // $this->call(DokterSeeder::class);
        // $this->call(ZatAktifSeeder::class);
        // $this->call(JasaMedisSeeder::class);
        // $this->call(WadahObatSeeder::class);
        // $this->call(MigrasiObatSeeder::class);
        // $this->call(TindakanSeeder::class);
        // $this->call(KonsultasiSeeder::class);
        // $this->call(PaketTindakanSeeder::class);
        $this->call(PaketTindakanDetailSeeder::class);
        // $this->call(MigrasiPasienSeeder::class);
        // $this->call(MigrasiVisitSeeder::class);
        // $this->call(MigrasiResepDokterSeeder::class);
        // $this->call(MigrasiResepFarmasiSeeder::class);
        // $this->call(MigrasiAsesmenDalamSeeder::class);
        // $this->call(MigrasiAsesmenUmumSeeder::class);
    }
}
