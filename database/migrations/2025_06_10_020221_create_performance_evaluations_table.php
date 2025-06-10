<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceEvaluationsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('performance_evaluation_periods');
            $table->foreignId('evaluator_id')->constrained('hrd_employee');
            $table->foreignId('evaluatee_id')->constrained('hrd_employee');
            $table->enum('status', ['pending', 'completed'])->default('pending'); // Make sure default is 'pending'
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_evaluations');
    }
}
