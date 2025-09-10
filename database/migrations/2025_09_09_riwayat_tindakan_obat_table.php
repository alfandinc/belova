<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::create('erm_riwayat_tindakan_obat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('riwayat_tindakan_id');
            $table->unsignedBigInteger('kode_tindakan_id');
            $table->unsignedBigInteger('obat_id');
            $table->integer('qty')->default(1);
            $table->string('dosis')->nullable();
            $table->string('satuan_dosis')->nullable();
            $table->timestamps();

            $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->onDelete('cascade');
            $table->foreign('kode_tindakan_id')->references('id')->on('erm_kode_tindakan')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_riwayat_tindakan_obat');
    }
};
