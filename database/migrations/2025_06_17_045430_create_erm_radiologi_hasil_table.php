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
        Schema::create('erm_radiologi_hasil', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->constrained('erm_visitations');
            $table->string('dokter_pengirim');
            $table->string('nama_pemeriksaan');
            $table->date('tanggal_pemeriksaan');
            $table->string('file_path');
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_radiologi_hasil');
    }
};
