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
        Schema::create('erm_asesmen_perawats', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keluhan_utama')->nullable();
            $table->string('alasan_kunjungan')->nullable();
            $table->string('kesadaran')->nullable();
            $table->string('td')->nullable();
            $table->string('nadi')->nullable();
            $table->string('rr')->nullable();
            $table->string('suhu')->nullable();
            $table->string('riwayat_psikososial')->nullable();
            $table->string('tb')->nullable();
            $table->string('bb')->nullable();
            $table->string('lla')->nullable();
            $table->string('diet')->nullable();
            $table->string('porsi')->nullable();
            $table->string('imt')->nullable();
            $table->string('presentase')->nullable();
            $table->string('efek')->nullable();
            $table->string('nyeri')->nullable();
            $table->string('p')->nullable();
            $table->string('q')->nullable();
            $table->string('r')->nullable();
            $table->string('t')->nullable();
            $table->string('onset')->nullable();
            $table->string('skor')->nullable();
            $table->string('kategori')->nullable();
            $table->string('kategori_risja')->nullable();
            $table->string('status_fungsional')->nullable();
            $table->json('masalah_keperawatan')->nullable();


            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_perawats');
    }
};
