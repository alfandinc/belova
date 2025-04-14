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
        Schema::create('erm_asesmen_dalam', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->string('tekanan_darah')->nullable();
            $table->float('suhu')->nullable();
            $table->float('berat_badan')->nullable();
            $table->float('tinggi_badan')->nullable();


            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_penyakit_dalam');
    }
};
