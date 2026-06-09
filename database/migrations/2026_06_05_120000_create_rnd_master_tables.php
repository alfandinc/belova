<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_master_brand', function (Blueprint $table) {
            $table->id();
            $table->string('nama_brand');
            $table->timestamps();
        });

        Schema::create('rnd_master_kemasan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kemasan');
            $table->string('ukuran');
            $table->enum('tipe_kemasan', ['primer', 'sekunder']);
            $table->timestamps();
        });

        Schema::create('rnd_master_sediaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sediaan');
            $table->timestamps();
        });

        Schema::create('rnd_master_vendor', function (Blueprint $table) {
            $table->id();
            $table->string('nama_vendor');
            $table->enum('tipe_vendor', ['produsen', 'kemasan', 'desain']);
            $table->string('no_hp')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rnd_master_bahan_aktif', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan_aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_master_bahan_aktif');
        Schema::dropIfExists('rnd_master_vendor');
        Schema::dropIfExists('rnd_master_sediaan');
        Schema::dropIfExists('rnd_master_kemasan');
        Schema::dropIfExists('rnd_master_brand');
    }
};