<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('satusehat_encounters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('visitation_id')->nullable();
            $table->string('pasien_id')->nullable();
            $table->unsignedBigInteger('klinik_id')->nullable();
            $table->string('satusehat_encounter_id')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('satusehat_encounters');
    }
};
