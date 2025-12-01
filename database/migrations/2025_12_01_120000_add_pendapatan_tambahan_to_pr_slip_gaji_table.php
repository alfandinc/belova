<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPendapatanTambahanToPrSlipGajiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('pr_slip_gaji', 'pendapatan_tambahan')) {
            Schema::table('pr_slip_gaji', function (Blueprint $table) {
                $table->json('pendapatan_tambahan')->nullable()->after('total_pendapatan');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('pr_slip_gaji', 'pendapatan_tambahan')) {
            Schema::table('pr_slip_gaji', function (Blueprint $table) {
                $table->dropColumn('pendapatan_tambahan');
            });
        }
    }
}
