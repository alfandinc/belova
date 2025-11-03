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
        Schema::table('pr_slip_gaji_dokter', function (Blueprint $table) {
            if (!Schema::hasColumn('pr_slip_gaji_dokter', 'peresepan_obat')) {
                $table->decimal('peresepan_obat', 15, 2)->nullable()->after('uang_duduk');
            }
            if (!Schema::hasColumn('pr_slip_gaji_dokter', 'rujuk_lab')) {
                $table->decimal('rujuk_lab', 15, 2)->nullable()->after('peresepan_obat');
            }
            if (!Schema::hasColumn('pr_slip_gaji_dokter', 'pembuatan_konten')) {
                $table->decimal('pembuatan_konten', 15, 2)->nullable()->after('rujuk_lab');
            }
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
            if (Schema::hasColumn('pr_slip_gaji_dokter', 'pembuatan_konten')) {
                $table->dropColumn('pembuatan_konten');
            }
            if (Schema::hasColumn('pr_slip_gaji_dokter', 'rujuk_lab')) {
                $table->dropColumn('rujuk_lab');
            }
            if (Schema::hasColumn('pr_slip_gaji_dokter', 'peresepan_obat')) {
                $table->dropColumn('peresepan_obat');
            }
        });
    }
};
