<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->foreignId('from_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('from_user_id');
        });
    }
};