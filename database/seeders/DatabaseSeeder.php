<?php

namespace Database\Seeders;

use App\Models\ERM\Konsultasi;
use App\Models\ERM\RadiologiKategori;
use App\Models\ERM\Tindakan;
use App\Models\HRD\PerformanceQuestion;
use App\Models\HRD\PerformanceQuestionCategory;
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
        // $this->call(KeluhanUtamaSeeder::class);
        // $this->call(TindakanSeeder::class);
        // $this->call(PaketTindakanSeeder::class);
        // $this->call(PaketTindakanDetailSeeder::class);
        // $this->call(PerformanceQuestionCategorySeeder::class);
        // $this->call(PerformanceQuestionSeeder::class);
        // $this->call(PerformanceEvaluationPeriodSeeder::class);
        // $this->call(PerformanceEvaluationSampleSeeder::class);
        // $this->call(DokterSeeder::class);
        // $this->call(ZatAktifSeeder::class);
        // $this->call(WadahObatSeeder::class);
        // $this->call(MigrasiObatSeeder::class);
        // $this->call(KonsultasiSeeder::class);
        // $this->call(MigrasiPasienSeeder::class);
        // $this->call(MigrasiVisitSeeder::class);
        // $this->call(MigrasiResepDokterSeeder::class);
        $this->call(MigrasiResepFarmasiSeeder::class);
        // $this->call(MigrasiAsesmenDalamSeeder::class);
        // $this->call(MigrasiAsesmenUmumSeeder::class);
        // $this->call(SOPSeeder::class);
        // $this->call(DivisionSeeder::class);
        // $this->call(PositionSeeder::class);
        // $this->call(EmployeeSeeder::class);
        // $this->call(KandunganObatSeeder::class);
        // $this->call(LabKategoriSeeder::class);
        // $this->call(LabTestSeeder::class);
        // $this->call(RadiologiKategoriSeeder::class);
        // $this->call(RadiologiTestSeeder::class);

    }
}
