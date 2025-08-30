<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->float('poin_kehadiran')->nullable()->after('kpi_poin');
            $table->float('poin_penilaian')->nullable()->after('poin_kehadiran');
            $table->float('poin_marketing')->nullable()->after('poin_penilaian');
        });
    }

    public function down()
    {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->dropColumn(['poin_kehadiran', 'poin_penilaian', 'poin_marketing']);
        });
    }
};
