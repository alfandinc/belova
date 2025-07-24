<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketing_content_plans', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_publish');
            $table->json('platform');
            $table->string('status');
            $table->json('jenis_konten');
            $table->string('target_audience')->nullable();
            $table->string('link_asset')->nullable();
            $table->string('link_publikasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_plans');
    }
};
