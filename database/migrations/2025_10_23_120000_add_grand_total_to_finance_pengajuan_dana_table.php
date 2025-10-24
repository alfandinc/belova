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
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            $table->decimal('grand_total', 15, 2)->default(0)->after('bukti_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_pengajuan_dana', function (Blueprint $table) {
            $table->dropColumn('grand_total');
        });
    }
};
