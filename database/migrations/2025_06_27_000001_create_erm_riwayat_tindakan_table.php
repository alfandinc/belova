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
        Schema::create('erm_riwayat_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->date('tanggal_tindakan');
            $table->unsignedBigInteger('tindakan_id');
            $table->unsignedBigInteger('paket_tindakan_id')->nullable();
            $table->timestamps();
            
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('tindakan_id')->references('id')->on('erm_tindakan')->onDelete('cascade');
            $table->foreign('paket_tindakan_id')->references('id')->on('erm_paket_tindakan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_riwayat_tindakan');
    }
};
