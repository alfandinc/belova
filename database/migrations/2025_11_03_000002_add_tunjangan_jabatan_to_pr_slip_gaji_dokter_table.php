<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTunjanganJabatanToPrSlipGajiDokterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->decimal('tunjangan_jabatan', 15, 2)->nullable()->after('jasa_tindakan');
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
            $table->dropColumn('tunjangan_jabatan');
        });
    }
}
