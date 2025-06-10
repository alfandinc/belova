<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceScoresTable extends Migration
{
    public function up()
    {
        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('performance_evaluations');
            $table->foreignId('question_id')->constrained('performance_questions');
            $table->integer('score')->comment('Score from 1 to 5');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_scores');
    }
}
