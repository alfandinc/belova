<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('satusehat_medications', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable()->index();
            $table->string('pasien_id')->nullable()->index();
            $table->unsignedBigInteger('klinik_id')->nullable()->index();
            $table->string('satusehat_medication_id')->nullable()->index();
            $table->json('obat_list')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('satusehat_medications');
    }
};
