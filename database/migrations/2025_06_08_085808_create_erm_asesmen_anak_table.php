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
        Schema::create('erm_asesmen_anak', function (Blueprint $table) {
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
            $table->string('riwayat_penyakit_keluarga')->nullable();
            $table->string('riwayat_makanan')->nullable();
            $table->string('riwayat_tumbang')->nullable();
            $table->string('riwayat_kehamilan')->nullable();
            $table->string('riwayat_persalinan')->nullable();
            $table->string('e')->nullable();
            $table->string('v')->nullable();
            $table->string('m')->nullable();
            $table->string('hsl')->nullable();
            $table->string('keadaan_umum')->nullable();
            $table->string('imunisasi_dasar')->nullable();
            $table->string('imunisasi_dasar_ket')->nullable();
            $table->string('imunisasi_lanjut')->nullable();
            $table->string('imunisasi_lanjut_ket')->nullable();
            $table->string('td')->nullable();
            $table->string('n')->nullable();
            $table->string('r')->nullable();
            $table->string('s')->nullable();
            $table->string('gizi')->nullable();
            $table->string('bb')->nullable();
            $table->string('tb')->nullable();
            $table->string('lk')->nullable();

            $table->string('kepala')->nullable();
            $table->string('leher')->nullable();
            $table->string('thorax')->nullable();
            $table->string('jantung')->nullable();
            $table->string('paru')->nullable();
            $table->string('abdomen')->nullable();
            $table->string('genitalia')->nullable();
            $table->string('extremitas')->nullable();
            $table->string('pemeriksaan_fisik_tambahan')->nullable();



            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_anak');
    }
};
