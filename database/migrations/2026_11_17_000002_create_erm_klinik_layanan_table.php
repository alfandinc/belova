<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Layanan (jenis kunjungan) per klinik. A klinik × jenis without a row is available (aktif), so nothing changes
 * until an admin switches a layanan off in Admin > Klinik Setting > tab Layanan; new kliniks start with all layanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_klinik_layanan')) {
            Schema::create('erm_klinik_layanan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('klinik_id')->index();
                $table->unsignedTinyInteger('jenis_kunjungan');
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->unique(['klinik_id', 'jenis_kunjungan']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_klinik_layanan');
    }
};
