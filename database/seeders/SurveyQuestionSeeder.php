<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Support\Facades\DB;

class SurveyQuestionSeeder extends Seeder
{
    public function run()
    {
        // Delete answers first to avoid foreign key constraint error
        DB::table('survey_answers')->delete();
        DB::table('survey_questions')->delete();
        SurveyQuestion::insert([
            [
                'question_text' => 'Seberapa puas Anda dengan keramahan staf kami?',
                'question_type' => 'emoji_scale',
                'options' => null,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_text' => 'Bagaimana penilaian Anda terhadap kebersihan fasilitas kami?',
                'question_type' => 'emoji_scale',
                'options' => null,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_text' => 'Seberapa puas Anda dengan kecepatan pelayanan kami?',
                'question_type' => 'emoji_scale',
                'options' => null,
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_text' => 'Seberapa besar kemungkinan Anda merekomendasikan kami ke orang lain?',
                'question_type' => 'emoji_scale',
                'options' => null,
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_text' => 'Seberapa puas Anda dengan pengalaman secara keseluruhan?',
                'question_type' => 'emoji_scale',
                'options' => null,
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_text' => 'Darimana kamu tahu klinik kami?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(["Tiktok", "Instagram", "Google", "Teman", "Lainnya"]),
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
