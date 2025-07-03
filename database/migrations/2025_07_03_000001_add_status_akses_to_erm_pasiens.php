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
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->string('status_akses')->default('normal')->after('status_pasien');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->dropColumn('status_akses');
        });
    }
};
