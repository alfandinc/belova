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
    Schema::create('erm_kode_tindakan_obat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_tindakan_id');
            $table->unsignedBigInteger('obat_id');
            $table->integer('qty')->default(1);
            $table->string('dosis')->nullable();
            $table->string('satuan_dosis')->nullable();
            $table->timestamps();

            $table->foreign('kode_tindakan_id')->references('id')->on('erm_kode_tindakan')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
    Schema::dropIfExists('erm_kode_tindakan_obat');
    }
};
