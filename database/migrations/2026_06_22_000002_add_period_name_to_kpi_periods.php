<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPeriodNameToKpiPeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kpi_periods', function (Blueprint $table) {
            $table->string('period_name')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kpi_periods', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_periods', 'period_name')) {
                $table->dropColumn('period_name');
            }
        });
    }
}
