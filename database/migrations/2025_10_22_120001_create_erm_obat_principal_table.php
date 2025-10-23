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
        Schema::create('erm_obat_principal', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('obat_id')->index();
            $table->unsignedBigInteger('principal_id')->index();
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('cascade');

            $table->unique(['obat_id', 'principal_id'], 'erm_obat_principal_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_obat_principal', function (Blueprint $table) {
            $table->dropForeign(['obat_id']);
            $table->dropForeign(['principal_id']);
        });
        Schema::dropIfExists('erm_obat_principal');
    }
};
