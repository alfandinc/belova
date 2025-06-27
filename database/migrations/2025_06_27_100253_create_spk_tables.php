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
        // Drop existing tables
        Schema::dropIfExists('erm_spk_details');
        Schema::dropIfExists('erm_spk');
        
        // Create new erm_spk table with proper structure
        Schema::create('erm_spk', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('pasien_id');
            $table->unsignedBigInteger('tindakan_id');
            $table->unsignedBigInteger('dokter_id');
            $table->date('tanggal_tindakan');
            $table->timestamps();
            
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('cascade');
            $table->foreign('tindakan_id')->references('id')->on('erm_tindakan')->onDelete('cascade');
            $table->foreign('dokter_id')->references('id')->on('erm_dokters')->onDelete('cascade');
        });
        
        // Create erm_spk_details table
        Schema::create('erm_spk_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_id');
            $table->unsignedBigInteger('sop_id');
            $table->string('penanggung_jawab');
            $table->boolean('sbk')->default(false);
            $table->boolean('sba')->default(false);
            $table->boolean('sdc')->default(false);
            $table->boolean('sdk')->default(false);
            $table->boolean('sdl')->default(false);
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('spk_id')->references('id')->on('erm_spk')->onDelete('cascade');
            $table->foreign('sop_id')->references('id')->on('erm_sop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_spk_details');
        Schema::dropIfExists('erm_spk');
    }
};
