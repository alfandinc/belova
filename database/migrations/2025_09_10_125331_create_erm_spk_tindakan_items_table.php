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
        Schema::create('erm_spk_tindakan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_tindakan_id');
            $table->unsignedBigInteger('kode_tindakan_id');
            $table->string('penanggung_jawab')->nullable();
            $table->string('sbk')->nullable(); // Sebelum Kalibrasi
            $table->string('sba')->nullable(); // Sebelum Antisepsi
            $table->string('sdc')->nullable(); // Sebelum Desinfeksi Cuci
            $table->string('sdk')->nullable(); // Sebelum Desinfeksi Kering
            $table->string('sdl')->nullable(); // Sebelum Desinfeksi Lain
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('spk_tindakan_id')->references('id')->on('erm_spk_tindakan')->onDelete('cascade');
            $table->foreign('kode_tindakan_id')->references('id')->on('erm_kode_tindakan')->onDelete('cascade');

            // Indexes
            $table->index(['spk_tindakan_id']);
            $table->index(['kode_tindakan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_spk_tindakan_items');
    }
};
