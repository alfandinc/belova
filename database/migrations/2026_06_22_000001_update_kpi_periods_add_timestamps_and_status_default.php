<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateKpiPeriodsAddTimestampsAndStatusDefault extends Migration
{
    /**
     * Run the migrations.
     * - add started_at, open_at, closed_at (nullable timestamps)
     * - update status enum to include 'draft' and set default to 'draft'
     * Uses raw SQL for enum modification to avoid DBAL dependency.
     */
    public function up()
    {
        // add timestamp columns if they don't exist
        Schema::table('kpi_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_periods', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('kpi_periods', 'open_at')) {
                $table->timestamp('open_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('kpi_periods', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('open_at');
            }
        });

        // Modify enum to include 'draft' and set default to 'draft'
        // Use raw statement to avoid requiring doctrine/dbal
        // Ensure we handle different DB engines by quoting appropriately for MySQL.
        DB::statement("ALTER TABLE `kpi_periods` MODIFY `status` ENUM('draft','started','open','closed') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revert enum to original values and default
        DB::statement("ALTER TABLE `kpi_periods` MODIFY `status` ENUM('started','open','closed') NOT NULL DEFAULT 'started'");

        // drop the added timestamp columns if they exist
        Schema::table('kpi_periods', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_periods', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('kpi_periods', 'open_at')) {
                $table->dropColumn('open_at');
            }
            if (Schema::hasColumn('kpi_periods', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }
}
