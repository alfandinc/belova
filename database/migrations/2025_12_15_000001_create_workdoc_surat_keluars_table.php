<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('workdoc_surat_keluars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('no_surat')->nullable();
            $table->string('jenis_surat')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('diajukan_for')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('tgl_dibuat')->nullable();
            $table->dateTime('tgl_diajukan')->nullable();
            $table->dateTime('tgl_disetujui')->nullable();
            $table->unsignedBigInteger('disetujui_by')->nullable();
            $table->string('lampiran')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workdoc_surat_keluars');
    }
};
