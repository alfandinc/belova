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
        Schema::create('erm_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('kode_obat')->nullable();
            $table->string('satuan')->nullable();
            $table->string('dosis')->nullable();
            $table->decimal('harga_net', 15, 2)->nullable();
            $table->decimal('harga_fornas', 15, 2)->nullable();
            $table->decimal('harga_nonfornas', 15, 2)->nullable();
            $table->integer('stok')->default(0);
            $table->string('kategori')->nullable();
            $table->foreignId('metode_bayar_id')->nullable()->constrained('erm_metode_bayar')->nullOnDelete();
            $table->boolean('status_aktif')->default(1);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_obat');
    }
};
