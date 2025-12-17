<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToErmKodeTindakan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('harga_bottom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
