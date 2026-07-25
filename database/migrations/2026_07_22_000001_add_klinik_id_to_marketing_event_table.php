<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_event', function (Blueprint $table) {
            $table->unsignedBigInteger('klinik_id')->nullable()->after('tanggal_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_event', function (Blueprint $table) {
            $table->dropColumn('klinik_id');
        });
    }
};