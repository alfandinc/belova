<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatusehatDokterMappingsTable extends Migration
{
    public function up()
    {
        Schema::create('satusehat_dokter_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dokter_id')->unique();
            $table->string('mapping_code')->nullable();
            $table->timestamps();

            $table->foreign('dokter_id')->references('id')->on('erm_dokters')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('satusehat_dokter_mappings');
    }
}
