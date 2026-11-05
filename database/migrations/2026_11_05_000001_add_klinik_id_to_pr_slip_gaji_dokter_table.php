<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dokter practising in several kliniks gets one slip per klinik per bulan.
     * Existing slips are assigned to the dokter's default klinik.
     * Not a unique index: older months hold duplicate slips, the controller enforces the rule.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('pr_slip_gaji_dokter', 'klinik_id')) {
            return;
        }

        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->unsignedBigInteger('klinik_id')->nullable()->after('dokter_id')->comment('Reference to erm_klinik.id');
            $table->index(['dokter_id', 'klinik_id', 'bulan'], 'pr_slip_gaji_dokter_dokter_klinik_bulan_index');
        });

        DB::table('pr_slip_gaji_dokter as s')
            ->join('erm_dokters as d', 'd.id', '=', 's.dokter_id')
            ->update(['s.klinik_id' => DB::raw('d.klinik_id')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('pr_slip_gaji_dokter', 'klinik_id')) {
            return;
        }

        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            $table->dropIndex('pr_slip_gaji_dokter_dokter_klinik_bulan_index');
            $table->dropColumn('klinik_id');
        });
    }
};
