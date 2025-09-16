<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inv_kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_id')->index();
            $table->integer('stok_awal')->default(0);
            $table->integer('stok_masuk')->default(0);
            $table->integer('stok_keluar')->default(0);
            $table->integer('stok_akhir')->default(0);
            $table->string('keterangan')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('tanggal')->nullable();
            $table->timestamps();

            $table->foreign('barang_id')->references('id')->on('inv_barang')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inv_kartu_stok');
    }
};
