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
        Schema::create('erm_suratmondok', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id', 6)->nullable();
            $table->foreignId('dokter_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->string('tujuan_igd')->nullable();
            $table->text('diagnosa')->nullable();
            $table->longText('instruksi_terapi')->nullable();
            $table->timestamps();

            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_suratmondok');
    }
};
