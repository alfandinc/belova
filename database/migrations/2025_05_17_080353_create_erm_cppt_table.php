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
        Schema::create('erm_cppt', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis_dokumen')->nullable();
            $table->string('jenis_kunjungan')->nullable();
            $table->longText('s')->nullable();
            $table->longText('o')->nullable();
            $table->longText('a')->nullable();
            $table->longText('p')->nullable();
            $table->string('instruksi')->nullable();
            $table->string('icd_10')->nullable();
            $table->string('dibaca')->nullable();
            $table->string('waktu_baca')->nullable();
            $table->string('handover')->nullable();
            $table->string('perawat_handover')->nullable();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_cppt');
    }
};
