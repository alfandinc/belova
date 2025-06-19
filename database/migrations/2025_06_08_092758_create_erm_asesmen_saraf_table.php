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
        Schema::create('erm_asesmen_saraf', function (Blueprint $table) {
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
            $table->string('kepala')->nullable();
            $table->string('leher')->nullable();
            $table->string('thorax')->nullable();
            $table->string('abdomen')->nullable();
            $table->string('genitalia')->nullable();
            $table->string('ext_atas')->nullable();
            $table->string('ext_bawah')->nullable();
            $table->string('keadaan_umum')->nullable();
            $table->string('td')->nullable();
            $table->string('n')->nullable();
            $table->string('r')->nullable();
            $table->string('s')->nullable();
            $table->string('e')->nullable();
            $table->string('m')->nullable();
            $table->string('v')->nullable();
            $table->string('hsl')->nullable();
            $table->string('vas')->nullable();
            $table->string('diameter_ket')->nullable();
            $table->string('diameter_1')->nullable();
            $table->string('diameter_2')->nullable();
            $table->string('isokor')->nullable();
            $table->string('anisokor')->nullable();
            $table->string('reflek_cahaya')->nullable();
            $table->string('reflek_cahaya1')->nullable();
            $table->string('reflek_cahaya2')->nullable();
            $table->string('reflek_cornea')->nullable();
            $table->string('reflek_cornea1')->nullable();
            $table->string('reflek_cornea2')->nullable();
            $table->string('nervus')->nullable();
            $table->string('kaku_kuduk')->nullable();
            $table->string('sign')->nullable();
            $table->string('brudzinki')->nullable();
            $table->string('kernig')->nullable();
            $table->string('doll')->nullable();
            $table->string('phenomena')->nullable();
            $table->string('vertebra')->nullable();
            $table->string('extremitas')->nullable();
            $table->string('gerak1')->nullable();
            $table->string('gerak2')->nullable();
            $table->string('gerak3')->nullable();
            $table->string('gerak4')->nullable();
            $table->string('reflek_fisio1')->nullable();
            $table->string('reflek_fisio2')->nullable();
            $table->string('reflek_fisio3')->nullable();
            $table->string('reflek_fisio4')->nullable();
            $table->string('reflek_pato1')->nullable();
            $table->string('reflek_pato2')->nullable();
            $table->string('reflek_pato3')->nullable();
            $table->string('reflek_pato4')->nullable();
            $table->longText('add_tambahan')->nullable();
            $table->string('clonus')->nullable();
            $table->string('sensibilitas')->nullable();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_saraf');
    }
};
