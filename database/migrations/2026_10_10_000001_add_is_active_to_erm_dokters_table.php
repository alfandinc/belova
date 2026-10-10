<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokter Nonaktif instead of delete: kunjungan, resep, slip gaji, ... keep pointing to the dokter,
 * while the dokter no longer shows in the pickers for new data. Existing dokters stay aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_dokters', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('status');
            $t->date('nonaktif_tanggal')->nullable()->after('is_active');
            $t->string('nonaktif_keterangan')->nullable()->after('nonaktif_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('erm_dokters', function (Blueprint $t) {
            $t->dropColumn(['is_active', 'nonaktif_tanggal', 'nonaktif_keterangan']);
        });
    }
};
