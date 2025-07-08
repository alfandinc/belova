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
        Schema::create('erm_hasil_eksternal', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('asal_lab');
            $table->string('nama_pemeriksaan');
            $table->date('tanggal_pemeriksaan');
            $table->string('dokter');
            $table->text('catatan')->nullable();
            $table->string('file_path')->nullable(); // For uploaded PDF files
            $table->timestamps();
            
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_hasil_eksternal');
    }
};
