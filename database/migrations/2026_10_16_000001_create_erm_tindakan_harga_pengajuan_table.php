<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga jual paket tindakan (erm_tindakan: harga, harga_diskon, diskon_active, harga_paket_visit) changes
 * go through approval, like erm_obat_harga_pengajuan. Each row proposes the full price set of one paket;
 * approved rows are the price history of the paket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_tindakan_harga_pengajuan', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tindakan_id');
            $t->decimal('harga_lama', 15, 2)->nullable();
            $t->decimal('harga_baru', 15, 2);
            $t->decimal('harga_diskon_lama', 15, 2)->nullable();
            $t->decimal('harga_diskon_baru', 15, 2)->nullable();
            $t->boolean('diskon_active_lama')->default(false);
            $t->boolean('diskon_active_baru')->default(false);
            $t->decimal('harga_paket_visit_lama', 15, 2)->nullable();
            $t->decimal('harga_paket_visit_baru', 15, 2)->nullable();
            // Obat/BHP cost of the paket's tindakan at the time of the proposal
            $t->decimal('biaya_bahan', 15, 2)->nullable();
            $t->string('alasan', 500)->nullable();
            // menunggu | disetujui | ditolak | dibatalkan
            $t->string('status', 20)->default('menunggu');
            $t->unsignedBigInteger('diajukan_by')->nullable();
            $t->unsignedBigInteger('disetujui_by')->nullable();
            $t->timestamp('disetujui_at')->nullable();
            $t->unsignedBigInteger('ditolak_by')->nullable();
            $t->timestamp('ditolak_at')->nullable();
            $t->string('alasan_tolak', 500)->nullable();
            $t->timestamp('dibatalkan_at')->nullable();
            $t->timestamps();

            $t->index(['tindakan_id', 'status']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_tindakan_harga_pengajuan');
    }
};
