<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrd_kpi_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('hrd_kpi_assessment_periods')->cascadeOnDelete();
            $table->foreignId('evaluatee_id')->constrained('hrd_employee')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('hrd_employee')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('hrd_division')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hrd_position')->nullOnDelete();
            $table->enum('evaluator_type', ['manager', 'hrd', 'head_manager']);
            $table->enum('status', ['pending', 'submitted'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'evaluatee_id', 'evaluator_id', 'evaluator_type'], 'hrd_kpi_assessment_unique_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_kpi_assessments');
    }
};