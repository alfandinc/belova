<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_event', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_event')->unique();
            $table->string('nama_event');
            $table->text('deskripsi_event')->nullable();
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('target_market')->nullable();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->string('dokumen_proposal')->nullable();
            $table->string('dokumen_laporan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_event');
    }
}
