<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_text');
            $table->string('question_type', 30); // emoji_scale, multiple_choice, etc
            $table->json('options')->nullable(); // for multiple choice
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('survey_questions')->onDelete('cascade');
            $table->string('answer');
            $table->string('submission_id', 64); // to group answers per submission
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_questions');
    }
};
