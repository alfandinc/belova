<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiScoresTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('indicators_id');

            $table->string('ss_category_name')->nullable();
            $table->decimal('ss_category_weight_percentage', 5, 2)->nullable();
            $table->string('ss_indicator_name')->nullable();
            $table->decimal('ss_indicator_weight_percentage', 5, 2)->nullable();

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('final_calculated_score', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('kpi_assessments')->onDelete('cascade');
            $table->foreign('indicators_id')->references('id')->on('kpi_indicators')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_scores');
    }
}
