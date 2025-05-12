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
        Schema::create('erm_resepdokter', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal_input')->useCurrent();
            $table->string('visitation_id');
            $table->string('obat_id');
            $table->integer('jumlah')->nullable();       // untuk non-racikan
            $table->string('dosis')->nullable();         // untuk racikan
            $table->integer('bungkus')->nullable();      // untuk racikan
            $table->integer('racikan_ke')->nullable();   // untuk racikan
            $table->string('aturan_pakai')->nullable();  // umum
            $table->string('wadah')->nullable();         // untuk racikan
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
        Schema::dropIfExists('erm_resepdokter');
    }
};
