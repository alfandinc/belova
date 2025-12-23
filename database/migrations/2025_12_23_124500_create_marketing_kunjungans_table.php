<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingKunjungansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_kunjungans', function (Blueprint $table) {
            $table->id();
            $table->string('instansi_tujuan')->nullable();
            $table->string('pic')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('instansi')->nullable();
            $table->string('status')->nullable();
            $table->string('bukti_kunjungan')->nullable();
            $table->text('hasil_kunjungan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_kunjungans');
    }
}
