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
        Schema::create('inv_pembelian_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('inv_barang')->onDelete('cascade');
            $table->foreignId('gedung_id')->constrained('inv_gedung')->onDelete('cascade');
            $table->date('tanggal_pembelian');
            $table->string('dibeli_dari')->nullable();
            $table->string('no_faktur')->nullable();
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->integer('jumlah')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_pembelian_barang');
    }
};
