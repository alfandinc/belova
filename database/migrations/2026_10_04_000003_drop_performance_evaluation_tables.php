<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the tables of the old performance evaluation module (its code has been removed).
 * Dropped child-first so foreign keys never block the drop; each drop is skipped when the table is missing.
 *
 * Not reversible: down() does not recreate the tables. Back up the data before running if it may be needed.
 */
return new class extends Migration
{
    private array $tables = [
        'performance_scores',
        'performance_evaluations',
        'performance_questions',
        'performance_question_categories',
        'performance_evaluation_periods',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // intentionally empty: the performance evaluation module no longer exists in code
    }
};
