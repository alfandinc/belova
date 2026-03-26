<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->date('deadline_date')
                ->nullable()
                ->after('task_date')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->dropIndex(['deadline_date']);
            $table->dropColumn('deadline_date');
        });
    }
};