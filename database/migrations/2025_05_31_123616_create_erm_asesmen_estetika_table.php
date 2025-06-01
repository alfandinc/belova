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
        Schema::create('erm_asesmen_estetika', function (Blueprint $table) {
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
            $table->json('kebiasaan_makan')->nullable();        // select2 multiple
            $table->json('kebiasaan_minum')->nullable();        // select2 multiple
            $table->enum('pola_tidur', ['Teratur', 'Begadang'])->nullable(); // choice
            $table->string('kontrasepsi')->nullable();          // select2 (single)
            $table->string('riwayat_perawatan')->nullable();    // select2 (single)
            $table->string('jenis_kulit')->nullable();          // select2 (single)
            $table->integer('kelembapan')->nullable();  // scale 1-3
            $table->integer('kekenyalan')->nullable();  // scale 1-3
            $table->json('area_kerutan')->nullable();           // select2 multiple
            $table->json('kelainan_kulit')->nullable();         // select2 multiple
            $table->string('anjuran')->nullable();              // select2 (single)
            $table->string('status_lokalis')->nullable();
            $table->string('ket_status_lokalis')->nullable();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_estetika');
    }
};
