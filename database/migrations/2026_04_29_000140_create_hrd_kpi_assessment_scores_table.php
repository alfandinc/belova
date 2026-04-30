<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrd_kpi_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('hrd_kpi_assessments')->cascadeOnDelete();
            $table->foreignId('period_indicator_id')->constrained('hrd_kpi_assessment_period_indicators')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'period_indicator_id'], 'hrd_kpi_assessment_scores_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_kpi_assessment_scores');
    }
};