<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            if (!Schema::hasColumn('pr_slip_gaji_dokter', 'pendapatan_tambahan')) {
                $table->json('pendapatan_tambahan')->nullable()->after('pembuatan_konten');
            }
        });
    }

    public function down()
    {
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            if (Schema::hasColumn('pr_slip_gaji_dokter', 'pendapatan_tambahan')) {
                $table->dropColumn('pendapatan_tambahan');
            }
        });
    }
};
