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
        Schema::create('inv_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruangan_id')->constrained('inv_ruangan')->onDelete('cascade');
            $table->foreignId('tipe_barang_id')->constrained('inv_tipe_barang')->onDelete('cascade');
            $table->string('name');
            $table->string('kode')->nullable();
            $table->string('satuan')->nullable();
            $table->string('merk')->nullable();
            $table->text('spec')->nullable();
            $table->integer('depreciation_rate')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_barang');
    }
};
