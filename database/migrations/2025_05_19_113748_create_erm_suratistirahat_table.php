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
        Schema::create('erm_suratistirahat', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id', 6)->nullable();
            $table->foreignId('dokter_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_hari');
            $table->timestamps();


            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_suratistirahat');
    }
};
