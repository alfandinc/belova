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
        Schema::create('erm_asesmen_penunjang', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->string('lab_gambar')->nullable();
            $table->string('lab_catatan')->nullable();
            $table->string('radiologi_gambar')->nullable();
            $table->string('radiologi_catatan')->nullable();
            $table->string('usg_gambar')->nullable();
            $table->string('usg_catatan')->nullable();
            $table->string('rekamjantung_gambar')->nullable();
            $table->string('rekamjantung_catatan')->nullable();
            $table->string('diagnosakerja_1')->nullable();
            $table->string('diagnosakerja_2')->nullable();
            $table->string('diagnosakerja_3')->nullable();
            $table->string('diagnosakerja_4')->nullable();
            $table->string('diagnosakerja_5')->nullable();
            $table->string('diagnosa_banding')->nullable();
            $table->string('masalah_medis')->nullable();
            $table->string('masalah_keperawatan')->nullable();
            $table->string('sasaran')->nullable();
            $table->longText('standing_order')->nullable();
            $table->string('rtl')->nullable();
            $table->string('ruang')->nullable();
            $table->string('dpip')->nullable();
            $table->string('indikasi')->nullable();
            $table->string('pengantar')->nullable();
            $table->string('rujuk_ke')->nullable();
            $table->string('rujuk_rs')->nullable();
            $table->string('rujuk_dokter')->nullable();
            $table->string('rujuk_puskesmas')->nullable();
            $table->string('homecare')->nullable();
            $table->string('tanggal_homecare')->nullable();
            $table->string('edukasi_1')->nullable();
            $table->string('edukasi_2')->nullable();
            $table->string('edukasi_3')->nullable();
            $table->string('hubungan_pasien')->nullable();
            $table->string('alasan')->nullable();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_penunjang');
    }
};
