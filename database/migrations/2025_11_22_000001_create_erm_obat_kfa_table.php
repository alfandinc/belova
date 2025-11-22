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
        Schema::create('satusehat_obat_kfa_mapping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('obat_id')->unique();
            $table->string('kfa_code')->nullable();
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satusehat_obat_kfa_mapping');
    }
};
