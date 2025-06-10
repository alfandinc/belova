<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HRD\PerformanceQuestionCategory;

class PerformanceQuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Kompetensi Kerja',
                'description' => 'Penilaian tentang kemampuan teknis dan profesional karyawan',
                'is_active' => true,
            ],
            [
                'name' => 'Perilaku dan Etika',
                'description' => 'Penilaian terhadap sikap, etika, dan perilaku di lingkungan kerja',
                'is_active' => true,
            ],
            [
                'name' => 'Kerja Tim dan Komunikasi',
                'description' => 'Penilaian terhadap kemampuan berinteraksi dan bekerja dalam tim',
                'is_active' => true,
            ],
            [
                'name' => 'Kepemimpinan',
                'description' => 'Penilaian terhadap kemampuan memimpin dan mengelola tim (untuk penilaian manager)',
                'is_active' => true,
            ],
            [
                'name' => 'Strategi dan Visi',
                'description' => 'Penilaian terhadap kemampuan strategis dan visi kepemimpinan',
                'is_active' => true,
            ],
            [
                'name' => 'Manajemen Sumber Daya',
                'description' => 'Penilaian terhadap kemampuan mengelola sumber daya manusia dan material',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            PerformanceQuestionCategory::create($category);
        }
    }
}
