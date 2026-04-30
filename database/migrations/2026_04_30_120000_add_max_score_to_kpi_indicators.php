<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hrd_kpi_assessment_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('hrd_kpi_assessment_indicators', 'max_score')) {
                $table->unsignedTinyInteger('max_score')->default(5)->after('weight_percentage');
            }
        });

        Schema::table('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('hrd_kpi_assessment_period_indicators', 'max_score')) {
                $table->unsignedTinyInteger('max_score')->default(5)->after('weight_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
            if (Schema::hasColumn('hrd_kpi_assessment_period_indicators', 'max_score')) {
                $table->dropColumn('max_score');
            }
        });

        Schema::table('hrd_kpi_assessment_indicators', function (Blueprint $table) {
            if (Schema::hasColumn('hrd_kpi_assessment_indicators', 'max_score')) {
                $table->dropColumn('max_score');
            }
        });
    }
};