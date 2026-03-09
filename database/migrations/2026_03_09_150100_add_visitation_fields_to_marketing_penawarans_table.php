<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_penawarans', function (Blueprint $table) {
            $table->unsignedBigInteger('klinik_id')->nullable()->after('pasien_id');
            $table->unsignedBigInteger('dokter_id')->nullable()->after('klinik_id');
            $table->unsignedBigInteger('metode_bayar_id')->nullable()->after('dokter_id');

            $table->foreign('klinik_id')->references('id')->on('erm_klinik')->nullOnDelete();
            $table->foreign('dokter_id')->references('id')->on('erm_dokters')->nullOnDelete();
            $table->foreign('metode_bayar_id')->references('id')->on('erm_metode_bayar')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_penawarans', function (Blueprint $table) {
            $table->dropForeign(['klinik_id']);
            $table->dropForeign(['dokter_id']);
            $table->dropForeign(['metode_bayar_id']);

            $table->dropColumn(['klinik_id', 'dokter_id', 'metode_bayar_id']);
        });
    }
};
