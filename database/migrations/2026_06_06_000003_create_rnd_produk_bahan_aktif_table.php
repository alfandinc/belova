<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_produk_bahan_aktif', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('bahan_aktif_id');
            $table->timestamps();

            $table->unique(['produk_id', 'bahan_aktif_id'], 'rnd_produk_bahan_aktif_unique');
            $table->foreign('produk_id', 'rnd_produk_bahan_aktif_produk_fk')
                ->references('id')
                ->on('rnd_produk')
                ->cascadeOnDelete();
            $table->foreign('bahan_aktif_id', 'rnd_produk_bahan_aktif_bahan_fk')
                ->references('id')
                ->on('rnd_master_bahan_aktif')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_produk_bahan_aktif');
    }
};