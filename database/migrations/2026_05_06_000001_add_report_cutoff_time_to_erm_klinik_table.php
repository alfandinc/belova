<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_klinik', function (Blueprint $table) {
            $table->time('report_cutoff_time')->default('00:00:00')->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('erm_klinik', function (Blueprint $table) {
            $table->dropColumn('report_cutoff_time');
        });
    }
};