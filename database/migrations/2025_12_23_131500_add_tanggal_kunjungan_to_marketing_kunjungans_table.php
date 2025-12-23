<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTanggalKunjunganToMarketingKunjungansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_kunjungans', function (Blueprint $table) {
            $table->date('tanggal_kunjungan')->nullable()->after('instansi_tujuan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_kunjungans', function (Blueprint $table) {
            $table->dropColumn('tanggal_kunjungan');
        });
    }
}
