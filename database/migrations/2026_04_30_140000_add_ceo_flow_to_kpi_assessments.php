<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE hrd_kpi_assessments MODIFY evaluator_type ENUM('manager', 'hrd', 'head_manager', 'ceo') NOT NULL");
    }

    public function down(): void
    {
        DB::table('hrd_kpi_assessments')
            ->where('evaluator_type', 'ceo')
            ->delete();

        DB::statement("ALTER TABLE hrd_kpi_assessments MODIFY evaluator_type ENUM('manager', 'hrd', 'head_manager') NOT NULL");
    }
};