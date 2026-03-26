<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_journal_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('task_date')->index();
            $table->string('title', 120);
            $table->string('note', 180)->nullable();
            $table->time('scheduled_time')->nullable();
            $table->string('status', 20)->default('todo')->index();
            $table->string('color_theme', 20)->default('rose');
            $table->string('icon', 16)->default('📝');
            $table->timestamps();

            $table->index(['user_id', 'task_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_journal_tasks');
    }
};