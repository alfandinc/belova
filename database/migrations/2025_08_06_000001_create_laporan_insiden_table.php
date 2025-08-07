<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laporan_insiden', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id');
            $table->string('penanggung_biaya')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->dateTime('tanggal_insiden');
            $table->string('insiden');
            $table->text('kronologi_insiden')->nullable();
            $table->string('jenis_insiden')->nullable();
            $table->string('pertama_lapor')->nullable();
            $table->string('insiden_pada')->nullable();
            $table->string('jenis_pasien')->nullable();
            $table->string('lokasi_insiden')->nullable();
            $table->unsignedBigInteger('spesialisasi_id')->nullable();
            $table->unsignedBigInteger('unit_penyebab')->nullable();
            $table->string('akibat_insiden')->nullable();
            $table->text('tindakan_dilakukan')->nullable();
            $table->string('tindakan_oleh')->nullable();
            $table->boolean('pernah_terjadi')->nullable();
            $table->text('langkah_diambil')->nullable();
            $table->text('pencegahan')->nullable();
            $table->unsignedBigInteger('pembuat_laporan');
            $table->unsignedBigInteger('penerima_laporan')->nullable();
            $table->date('tanggal_lapor')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('grading_resiko')->nullable();
            $table->timestamps();

            $table->foreign('pasien_id')->references('id')->on('erm_pasiens');
            $table->foreign('spesialisasi_id')->references('id')->on('erm_spesialisasis');
            $table->foreign('unit_penyebab')->references('id')->on('hrd_division');
            $table->foreign('pembuat_laporan')->references('id')->on('users');
            $table->foreign('penerima_laporan')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laporan_insiden');
    }
};
