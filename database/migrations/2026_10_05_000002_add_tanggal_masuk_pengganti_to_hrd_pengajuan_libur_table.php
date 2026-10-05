<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti libur: the scheduled Sunday(s) the employee worked that this day off replaces,
 * stored as a JSON list of dates, e.g. ["2026-10-04"]. NULL for cuti tahunan and older requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrd_pengajuan_libur', function (Blueprint $table) {
            $table->json('tanggal_masuk_pengganti')->nullable()->after('jenis_libur');
        });
    }

    public function down(): void
    {
        Schema::table('hrd_pengajuan_libur', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk_pengganti');
        });
    }
};
