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
        // // Add riwayat_tindakan_id to erm_inform_consent table
        // Schema::table('erm_inform_consent', function (Blueprint $table) {
        //     $table->unsignedBigInteger('riwayat_tindakan_id')->nullable()->after('paket_id');
        //     $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->onDelete('cascade');
        // });
        
        // Add riwayat_tindakan_id to erm_spk table
        Schema::table('erm_spk', function (Blueprint $table) {
            $table->unsignedBigInteger('riwayat_tindakan_id')->nullable()->after('tanggal_tindakan');
            $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('erm_inform_consent', function (Blueprint $table) {
        //     $table->dropForeign(['riwayat_tindakan_id']);
        //     $table->dropColumn('riwayat_tindakan_id');
        // });
        
        Schema::table('erm_spk', function (Blueprint $table) {
            $table->dropForeign(['riwayat_tindakan_id']);
            $table->dropColumn('riwayat_tindakan_id');
        });
    }
};
