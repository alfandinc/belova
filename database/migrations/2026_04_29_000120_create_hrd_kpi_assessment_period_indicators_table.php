<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hrd_kpi_assessment_period_indicators')) {
            Schema::create('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('period_id')->constrained('hrd_kpi_assessment_periods')->cascadeOnDelete();
                $table->foreignId('source_indicator_id')->nullable()->constrained('hrd_kpi_assessment_indicators')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('indicator_type', ['global', 'technical']);
                $table->foreignId('position_id')->nullable()->constrained('hrd_position')->nullOnDelete();
                $table->decimal('weight_percentage', 5, 2);
                $table->string('score_label_1')->nullable();
                $table->string('score_label_2')->nullable();
                $table->string('score_label_3')->nullable();
                $table->string('score_label_4')->nullable();
                $table->string('score_label_5')->nullable();
                $table->timestamps();
            });
        }

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'hrd_kpi_assessment_period_indicators')
            ->where('index_name', 'kpi_period_ind_type_idx')
            ->exists();

        if (!$indexExists) {
            Schema::table('hrd_kpi_assessment_period_indicators', function (Blueprint $table) {
                $table->index(['period_id', 'indicator_type'], 'kpi_period_ind_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_kpi_assessment_period_indicators');
    }
};