<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HRD\{
    PerformanceQuestion,
    PerformanceQuestionCategory
};

class PerformanceQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get category IDs
        $categories = PerformanceQuestionCategory::all()->keyBy('name');

        // Manager to Employee questions (from the image)
        $managerToEmployeeQuestions = [
            'Kompetensi Kerja' => [
                'Menyelesaikan tugas dengan baik dan tepat waktu',
                'Teliti dan detail dalam bekerja',
                'Mampu bekerja di bawah tekanan',
                'Bertanggungjawab atas tugasnya',
                'Menunjukan inisitif dalam bekerja',
            ],
            'Perilaku dan Etika' => [
                'Menjaga sikap dan sopan santun di tempat kerja',
                'Menunjukkan kejujuran dan integritas',
                'Menjaga hubungan baik dengan rekan kerja',
                'Mampu beradaptasi dengan perubahan',
                'Mengelola emosi dengan baik',
            ],
            'Kerja Tim dan Komunikasi' => [
                'Aktif berdiskusi dan memberi masukan positif',
                'Mendengarkan pendapat oranglain',
                'Bersedia membantu rekan kerja',
                'Terbuka terhadap masukan dan kritik',
            ],
        ];

        foreach ($managerToEmployeeQuestions as $categoryName => $questions) {
            $categoryId = $categories[$categoryName]->id;
            foreach ($questions as $questionText) {
                PerformanceQuestion::create([
                    'question_text' => $questionText,
                    'category_id' => $categoryId,
                    'evaluation_type' => 'manager_to_employee',
                    'is_active' => true,
                ]);
            }
        }

        // Employee to Manager questions
        $employeeToManagerQuestions = [
            'Kepemimpinan' => [
                'Memberikan arahan yang jelas tentang pekerjaan',
                'Mendelegasikan tugas secara adil dan efektif',
                'Mampu membuat keputusan tepat pada situasi sulit',
                'Memberikan feedback konstruktif secara rutin',
                'Menjadi contoh dan teladan bagi tim',
            ],
            'Perilaku dan Etika' => [
                'Bersikap adil terhadap semua anggota tim',
                'Menunjukkan kejujuran dan integritas dalam bekerja',
                'Menghargai pendapat dan kontribusi anggota tim',
                'Konsisten dalam penerapan aturan dan kebijakan',
                'Melindungi tim dari tekanan eksternal yang tidak perlu',
            ],
            'Kerja Tim dan Komunikasi' => [
                'Berkomunikasi secara jelas dan efektif',
                'Terbuka terhadap ide dan masukan dari tim',
                'Melibatkan anggota tim dalam pengambilan keputusan',
                'Mendorong kolaborasi antar anggota tim',
                'Menyelesaikan konflik secara konstruktif',
            ],
        ];

        foreach ($employeeToManagerQuestions as $categoryName => $questions) {
            $categoryId = $categories[$categoryName]->id;
            foreach ($questions as $questionText) {
                PerformanceQuestion::create([
                    'question_text' => $questionText,
                    'category_id' => $categoryId,
                    'evaluation_type' => 'employee_to_manager',
                    'is_active' => true,
                ]);
            }
        }

        // HRD to Manager questions
        $hrdToManagerQuestions = [
            'Kepemimpinan' => [
                'Menunjukkan kepemimpinan yang efektif dalam mengelola tim',
                'Menciptakan lingkungan kerja yang positif dan produktif',
                'Mengembangkan kompetensi dan karir anggota tim',
                'Mengatasi konflik dan masalah dengan tepat',
                'Memberikan mentoring dan coaching pada anggota tim',
            ],
            'Strategi dan Visi' => [
                'Menyelaraskan tujuan tim dengan visi perusahaan',
                'Mengembangkan strategi yang efektif untuk mencapai target',
                'Beradaptasi dengan perubahan industri dan pasar',
                'Mengambil keputusan strategis yang tepat',
                'Mendorong inovasi dalam divisi',
            ],
            'Manajemen Sumber Daya' => [
                'Mengelola anggaran dengan efisien',
                'Mengoptimalkan penggunaan sumber daya yang tersedia',
                'Melakukan rekrutmen dan seleksi yang tepat',
                'Mengelola dan mengembangkan talenta dalam tim',
                'Memastikan kepatuhan terhadap kebijakan dan prosedur perusahaan',
            ],
        ];

        foreach ($hrdToManagerQuestions as $categoryName => $questions) {
            $categoryId = $categories[$categoryName]->id;
            foreach ($questions as $questionText) {
                PerformanceQuestion::create([
                    'question_text' => $questionText,
                    'category_id' => $categoryId,
                    'evaluation_type' => 'hrd_to_manager',
                    'is_active' => true,
                ]);
            }
        }

        // Manager to HRD questions
        $managerToHRDQuestions = [
            'Kompetensi Kerja' => [
                'Melaksanakan proses rekrutmen dengan efektif dan tepat waktu',
                'Mengembangkan program pelatihan yang sesuai dengan kebutuhan',
                'Mengelola masalah kepegawaian dengan profesional',
                'Mengimplementasikan kebijakan SDM dengan konsisten',
                'Memberikan dukungan terhadap kebutuhan divisi',
            ],
            'Perilaku dan Etika' => [
                'Menjaga kerahasiaan informasi personel',
                'Menangani masalah sensitif dengan bijaksana',
                'Bersikap adil dalam pengambilan keputusan terkait karyawan',
                'Menunjukkan profesionalisme dalam bekerja',
                'Membangun hubungan baik dengan semua divisi',
            ],
            'Kerja Tim dan Komunikasi' => [
                'Mengkomunikasikan kebijakan dan prosedur SDM dengan jelas',
                'Responsif terhadap kebutuhan divisi lain',
                'Menjembatani komunikasi antara manajemen dan staf',
                'Memberikan konsultasi yang bermanfaat untuk manajemen',
                'Terbuka terhadap masukan untuk perbaikan sistem dan kebijakan',
            ],
        ];

        foreach ($managerToHRDQuestions as $categoryName => $questions) {
            $categoryId = $categories[$categoryName]->id;
            foreach ($questions as $questionText) {
                PerformanceQuestion::create([
                    'question_text' => $questionText,
                    'category_id' => $categoryId,
                    'evaluation_type' => 'manager_to_hrd',
                    'is_active' => true,
                ]);
            }
        }
    }
}
