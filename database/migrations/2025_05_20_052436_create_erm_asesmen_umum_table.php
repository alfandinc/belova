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
        Schema::create('erm_asesmen_umum', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->string('autoanamnesis')->nullable();
            $table->string('alloanamnesis')->nullable();
            $table->string('anamnesis1')->nullable();
            $table->string('anamnesis2')->nullable();
            $table->text('keluhan_utama')->nullable();
            $table->string('riwayat_penyakit_sekarang')->nullable();
            $table->string('allo_dengan')->nullable();
            $table->string('hasil_allo')->nullable();
            $table->string('riwayat_penyakit_dahulu')->nullable();
            $table->string('obat_dikonsumsi')->nullable();
            $table->string('keadaan_umum')->nullable();
            $table->string('td')->nullable();
            $table->string('n')->nullable();
            $table->string('r')->nullable();
            $table->string('s')->nullable();
            $table->string('e')->nullable();
            $table->string('m')->nullable();
            $table->string('v')->nullable();
            $table->string('hsl')->nullable();
            $table->string('kepala')->nullable();
            $table->string('leher')->nullable();
            $table->string('thorax')->nullable();
            $table->string('abdomen')->nullable();
            $table->string('genitalia')->nullable();
            $table->string('ext_atas')->nullable();
            $table->string('ext_bawah')->nullable();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_umum');
    }
};
