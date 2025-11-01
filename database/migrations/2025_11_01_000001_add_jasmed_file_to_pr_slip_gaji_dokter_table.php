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
            if (!Schema::hasColumn('pr_slip_gaji_dokter', 'jasmed_file')) {
                $table->string('jasmed_file')->nullable()->after('total_gaji')->comment('Path to uploaded jasmed file (public disk)');
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
            if (Schema::hasColumn('pr_slip_gaji_dokter', 'jasmed_file')) {
                $table->dropColumn('jasmed_file');
            }
        });
    }
};
