<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_questions');
    }

    public function down()
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->string('klinik_name')->nullable();
            $table->string('question_text');
            $table->string('question_type', 30);
            $table->json('options')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('survey_questions')->onDelete('cascade');
            $table->string('answer');
            $table->string('submission_id', 64);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }
};
