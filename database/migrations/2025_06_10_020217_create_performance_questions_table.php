<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('performance_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->foreignId('category_id')->constrained('performance_question_categories');
            $table->enum('evaluation_type', ['hrd_to_manager', 'manager_to_employee', 'employee_to_manager', 'manager_to_hrd']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_questions');
    }
}
