<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obat "Stop Pengadaan": no longer purchased. Still sold until the remaining stock runs out,
 * but forecast never suggests ordering it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat', function (Blueprint $t) {
            $t->boolean('stop_pengadaan')->default(false)->after('wajib_stok');
            $t->string('stop_pengadaan_alasan', 500)->nullable()->after('stop_pengadaan');
            $t->timestamp('stop_pengadaan_at')->nullable()->after('stop_pengadaan_alasan');
            $t->unsignedBigInteger('stop_pengadaan_by')->nullable()->after('stop_pengadaan_at');
        });
    }

    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $t) {
            $t->dropColumn(['stop_pengadaan', 'stop_pengadaan_alasan', 'stop_pengadaan_at', 'stop_pengadaan_by']);
        });
    }
};
