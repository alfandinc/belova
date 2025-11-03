<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOvertimeAndPotonganLainToPrSlipGajiDokterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->decimal('overtime', 15, 2)->nullable()->after('pembuatan_konten');
            $table->decimal('potongan_lain', 15, 2)->nullable()->after('bagi_hasil');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->dropColumn(['overtime', 'potongan_lain']);
        });
    }
}
