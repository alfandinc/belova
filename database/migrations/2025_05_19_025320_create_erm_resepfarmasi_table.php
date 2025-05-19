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
        Schema::create('erm_resepfarmasi', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('obat_id');
            $table->integer('jumlah')->nullable();
            $table->string('dosis')->nullable();
            $table->integer('bungkus')->nullable();
            $table->string('racikan_ke')->nullable();
            $table->string('aturan_pakai')->nullable();
            $table->string('wadah')->nullable();
            $table->integer('harga')->nullable();
            $table->integer('diskon')->nullable();
            $table->integer('total')->nullable();
            $table->unsignedBigInteger('dokter_id')->nullable(); // trace to source doctor
            $table->timestamps();


            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_resepfarmasi');
    }
};
