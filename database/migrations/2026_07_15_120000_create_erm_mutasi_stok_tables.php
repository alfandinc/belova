<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_mutasi')->unique();
            $table->foreignId('gudang_id')->constrained('erm_gudang');
            $table->enum('jenis_mutasi', ['keluar', 'masuk']);
            $table->string('status')->default('done');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index(['gudang_id', 'jenis_mutasi']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('erm_mutasi_stok_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutasi_stok_id')->constrained('erm_mutasi_stok')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('erm_obat');
            $table->decimal('jumlah', 12, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['mutasi_stok_id', 'obat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_mutasi_stok_items');
        Schema::dropIfExists('erm_mutasi_stok');
    }
};