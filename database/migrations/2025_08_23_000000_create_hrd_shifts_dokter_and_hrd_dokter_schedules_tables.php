<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hrd_shifts_dokter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dokter_id');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();

            $table->foreign('dokter_id')->references('id')->on('erm_dokters')->onDelete('cascade');
        });

        Schema::create('hrd_dokter_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dokter_id');
            $table->date('date');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();

            $table->foreign('dokter_id')->references('id')->on('erm_dokters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hrd_dokter_schedules');
        Schema::dropIfExists('hrd_shifts_dokter');
    }
};
