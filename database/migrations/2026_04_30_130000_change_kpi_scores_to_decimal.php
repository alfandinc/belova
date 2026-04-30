<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE hrd_kpi_assessment_scores MODIFY score DECIMAL(5,2) UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE hrd_kpi_assessment_scores SET score = ROUND(score)');
        DB::statement('ALTER TABLE hrd_kpi_assessment_scores MODIFY score TINYINT UNSIGNED NOT NULL');
    }
};