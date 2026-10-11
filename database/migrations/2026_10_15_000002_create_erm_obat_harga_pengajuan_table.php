<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga jual (erm_obat.harga_nonfornas) changes go through approval. Each row is one proposed price;
 * approved rows are the price history of the obat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_obat_harga_pengajuan', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('obat_id');
            $t->decimal('harga_lama', 15, 2)->nullable();
            $t->decimal('harga_baru', 15, 2);
            // Cost at the time of the proposal, so the approver (and later readers) see the margin it was based on
            $t->decimal('hpp', 15, 2)->nullable();
            $t->decimal('hna', 15, 2)->nullable();
            $t->decimal('margin_lama', 8, 2)->nullable();
            $t->decimal('margin_baru', 8, 2)->nullable();
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

            $t->index(['obat_id', 'status']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_obat_harga_pengajuan');
    }
};
