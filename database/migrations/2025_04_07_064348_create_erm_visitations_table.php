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
        Schema::create('erm_visitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pasien_id');
            $table->unsignedBigInteger('dokter_id');
            $table->unsignedBigInteger('metode_bayar_id')->nullable();
            $table->tinyInteger('progress')->default(1); // 1 = perawat, 2 = dokter, dll
            $table->enum('status', ['asesmen', 'cppt'])->default('asesmen');
            $table->date('tanggal_visitation');
            $table->timestamps();

            // Foreign keys
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('cascade');
            $table->foreign('metode_bayar_id')->references('id')->on('erm_metode_bayar')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_visitations');
    }
};
