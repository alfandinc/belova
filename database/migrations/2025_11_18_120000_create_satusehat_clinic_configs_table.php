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
        Schema::create('satusehat_clinic_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('klinik_id')->nullable();
            $table->string('auth_url')->nullable();
            $table->string('base_url')->nullable();
            $table->string('consent_url')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('organization_id')->nullable();
            $table->text('token')->nullable();
            $table->timestamps();

            $table->foreign('klinik_id')->references('id')->on('erm_klinik')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satusehat_clinic_configs');
    }
};
