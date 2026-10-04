<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared KPI categories: a category flagged is_shared has one indicator set that applies to every position.
 * The weight of each indicator inside a shared category lives on the indicator (shared_weight_percentage),
 * instead of per position in kpi_position_indicators.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_indicator_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_indicator_categories', 'is_shared')) {
                $table->boolean('is_shared')->default(false)->after('evaluator_position_id');
            }
        });

        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_indicators', 'shared_weight_percentage')) {
                $table->decimal('shared_weight_percentage', 5, 2)->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_indicators', 'shared_weight_percentage')) {
                $table->dropColumn('shared_weight_percentage');
            }
        });

        Schema::table('kpi_indicator_categories', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_indicator_categories', 'is_shared')) {
                $table->dropColumn('is_shared');
            }
        });
    }
};
