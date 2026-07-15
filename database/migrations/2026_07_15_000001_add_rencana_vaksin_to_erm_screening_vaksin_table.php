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
        Schema::table('erm_screening_vaksin', function (Blueprint $table) {
            $table->string('rencana_vaksin')->nullable()->after('vaksinasi_4_minggu_terakhir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_screening_vaksin', function (Blueprint $table) {
            $table->dropColumn('rencana_vaksin');
        });
    }
};