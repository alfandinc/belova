<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstansiToWorkdocKemitraansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workdoc_kemitraans', function (Blueprint $table) {
            $table->string('instansi')->nullable()->after('partner_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workdoc_kemitraans', function (Blueprint $table) {
            $table->dropColumn('instansi');
        });
    }
}
