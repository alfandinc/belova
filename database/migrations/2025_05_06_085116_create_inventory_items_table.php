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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number');      // NO INV
            $table->string('name');                  // NAMA BARANG
            $table->string('condition');             // KONDISI
            $table->integer('quantity');             // JML
            $table->bigInteger('unit_price');        // HARGA SATUAN
            $table->bigInteger('book_value');        // NILAI BUKU
            $table->year('purchase_year');           // TAHUN PENGADAAN
            $table->text('note')->nullable();        // KETERANGAN
            $table->decimal('initial_depreciation', 15, 2)->nullable();    // PENYUSUTAN AWAL
            $table->decimal('annual_depreciation', 15, 2)->nullable();     // PENYUSUTAN
            $table->decimal('accumulated_depreciation', 15, 2)->nullable(); // AKM. PENYUSUTAN
            $table->decimal('residual_value', 15, 2)->nullable();          // NILAI RESIDU
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
