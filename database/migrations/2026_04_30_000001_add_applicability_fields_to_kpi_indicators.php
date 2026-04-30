<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hrd_kpi_assessment_indicators', function (Blueprint $table) {
            $table->string('applicability_scope')->default('hrd_to_all')->after('indicator_type');
        });

        Schema::table('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
            $table->string('applicability_scope')->default('hrd_to_all')->after('indicator_type');
        });

        DB::table('hrd_kpi_assessment_indicators')
            ->where('indicator_type', 'technical')
            ->update(['applicability_scope' => 'manager_to_employee']);

        DB::table('hrd_kpi_assessment_period_indicators')
            ->where('indicator_type', 'technical')
            ->update(['applicability_scope' => 'manager_to_employee']);
    }

    public function down(): void
    {
        Schema::table('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
            $table->dropColumn('applicability_scope');
        });

        Schema::table('hrd_kpi_assessment_indicators', function (Blueprint $table) {
            $table->dropColumn('applicability_scope');
        });
    }
};