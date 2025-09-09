<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('erm_tindakan_kode_tindakan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tindakan_id');
            $table->unsignedBigInteger('kode_tindakan_id');
            $table->timestamps();

            $table->foreign('tindakan_id')->references('id')->on('erm_tindakan')->onDelete('cascade');
            $table->foreign('kode_tindakan_id')->references('id')->on('erm_kode_tindakan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_tindakan_kode_tindakan');
    }
};
