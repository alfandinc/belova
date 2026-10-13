<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily Journal: longer notes, and recurring tasks (a template that creates one task per matching day).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('daily_journal_recurrences')) {
            Schema::create('daily_journal_recurrences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 120);
                $table->text('note')->nullable();
                $table->time('scheduled_time')->nullable();
                // daily | weekdays | weekly (weekday 1-7, ISO) | monthly (month_day 1-31)
                $table->string('pattern', 20);
                $table->unsignedTinyInteger('weekday')->nullable();
                $table->unsignedTinyInteger('month_day')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('last_generated_date')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'start_date']);
            });
        }

        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->text('note')->nullable()->change();

            if (!Schema::hasColumn('daily_journal_tasks', 'recurrence_id')) {
                $table->foreignId('recurrence_id')
                    ->nullable()
                    ->after('from_user_id')
                    ->constrained('daily_journal_recurrences')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('daily_journal_tasks', 'recurrence_id')) {
                $table->dropConstrainedForeignId('recurrence_id');
            }
            $table->string('note', 180)->nullable()->change();
        });

        Schema::dropIfExists('daily_journal_recurrences');
    }
};
