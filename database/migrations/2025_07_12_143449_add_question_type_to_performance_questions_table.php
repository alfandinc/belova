<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('performance_questions', function (Blueprint $table) {
            $table->enum('question_type', ['score', 'text'])->default('score')->after('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
