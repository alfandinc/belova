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
        Schema::create('erm_spk_tindakan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('riwayat_tindakan_id');
            $table->date('tanggal_tindakan');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->onDelete('cascade');

            // Indexes
            $table->index(['riwayat_tindakan_id']);
            $table->index(['tanggal_tindakan']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_spk_tindakan');
    }
};
