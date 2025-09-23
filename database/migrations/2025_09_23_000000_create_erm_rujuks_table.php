<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erm_rujuks', function (Blueprint $table) {
            $table->id();
            // pasien_id in other tables uses string length 6 and is nullable
            $table->string('pasien_id', 6)->nullable();
            // dokters use unsigned big integers (id()) in their table
            $table->foreignId('dokter_pengirim_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->foreignId('dokter_tujuan_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->string('jenis_permintaan')->nullable(); // Rujuk/Konsultasi
            $table->text('keterangan')->nullable();
            $table->text('penunjang')->nullable();
            // visitation id in visitations is string primary key
            $table->string('visitation_id')->nullable();
            $table->timestamps();

            // foreign key to pasien (string id)
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
            // foreign key to visitation (string primary key)
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_rujuks');
    }
};
