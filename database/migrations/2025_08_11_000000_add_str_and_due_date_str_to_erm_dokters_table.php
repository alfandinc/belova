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
        Schema::table('erm_dokters', function (Blueprint $table) {
            $table->string('str')->nullable()->after('status');
            $table->date('due_date_str')->nullable()->after('str');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_dokters', function (Blueprint $table) {
            $table->dropColumn(['str', 'due_date_str']);
        });
    }
};
