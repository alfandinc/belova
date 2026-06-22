<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiAssessmentsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');

            $table->unsignedBigInteger('evaluator_employee_id');
            $table->unsignedBigInteger('evaluator_position_id');

            $table->unsignedBigInteger('evaluatee_employee_id');
            $table->unsignedBigInteger('evaluatee_position_id');

            $table->enum('assessment_type', ['parent_to_child', 'child_to_parent'])->default('parent_to_child');
            $table->enum('status', ['pending', 'done'])->default('pending');
            $table->timestamps();

            $table->foreign('period_id')->references('id')->on('kpi_periods')->onDelete('cascade');

            $table->foreign('evaluator_employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
            $table->foreign('evaluator_position_id')->references('id')->on('hrd_position')->onDelete('cascade');

            $table->foreign('evaluatee_employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
            $table->foreign('evaluatee_position_id')->references('id')->on('hrd_position')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_assessments');
    }
}
