<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hrd_kpi_assessment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('assessment_month')->unique();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('snapshot_taken_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_kpi_assessment_periods');
    }
};