<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_permintaan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permintaan_id');
            $table->unsignedBigInteger('obat_id');
            $table->unsignedBigInteger('pemasok_id');
            $table->integer('jumlah_box');
            $table->integer('qty_total');
            $table->timestamps();

            $table->foreign('permintaan_id')->references('id')->on('erm_permintaan')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('id')->on('erm_pemasok')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_permintaan_items');
    }
};
