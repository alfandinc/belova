<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('satusehat_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('klinik_id')->nullable();
            $table->string('location_id')->nullable();
            $table->text('description')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('line')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('identifier_value')->nullable();
            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('satusehat_locations');
    }
};
