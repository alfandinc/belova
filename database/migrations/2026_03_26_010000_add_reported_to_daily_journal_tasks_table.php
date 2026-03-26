<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->boolean('reported')
                ->default(false)
                ->after('status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('daily_journal_tasks', function (Blueprint $table) {
            $table->dropIndex(['reported']);
            $table->dropColumn('reported');
        });
    }
};