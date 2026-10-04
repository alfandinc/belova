<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the tables of the old HRD KPI assessment system (replaced by the kpi_* tables).
 * Dropped child-first so foreign keys never block the drop; each drop is skipped when the table is missing.
 *
 * Not reversible: the old system's code has been removed, so down() does not recreate the tables.
 */
return new class extends Migration
{
    private array $tables = [
        'hrd_kpi_assessment_scores',
        'hrd_kpi_assessments',
        'hrd_kpi_assessment_period_indicators',
        'hrd_kpi_assessment_indicators',
        'hrd_kpi_assessment_periods',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // intentionally empty: the old HRD KPI system no longer exists in code
    }
};
