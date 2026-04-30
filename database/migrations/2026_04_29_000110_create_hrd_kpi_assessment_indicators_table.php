<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrd_kpi_assessment_indicators', function (Blueprint $table) {
            $table->id();
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
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_kpi_assessment_indicators');
    }
};