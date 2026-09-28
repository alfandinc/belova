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
        Schema::table('erm_visitations', function (Blueprint $table) {
            // Billing / rawat jalan lists filter by status + date range
            $table->index(['status_kunjungan', 'tanggal_visitation'], 'erm_visitations_status_tanggal_index');
            // "first visit" subquery looks up earlier visits of the same pasien at the same klinik
            $table->index(['pasien_id', 'klinik_id', 'tanggal_visitation'], 'erm_visitations_pasien_klinik_tanggal_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_visitations', function (Blueprint $table) {
            $table->dropIndex('erm_visitations_status_tanggal_index');
            $table->dropIndex('erm_visitations_pasien_klinik_tanggal_index');
        });
    }
};
