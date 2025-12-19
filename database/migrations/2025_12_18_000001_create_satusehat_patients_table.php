<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('satusehat_patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('visitation_id')->nullable();
            $table->string('pasien_id')->nullable();
            $table->string('pasien_name')->nullable();
            $table->string('satusehat_patient_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('satusehat_patients');
    }
};
