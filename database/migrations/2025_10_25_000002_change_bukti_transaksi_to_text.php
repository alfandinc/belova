<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw statement to alter column type to TEXT. This assumes MySQL.
        // If you use a different DB, adjust accordingly.
        try {
            DB::statement("ALTER TABLE `finance_pengajuan_dana` MODIFY `bukti_transaksi` TEXT NULL");
        } catch (\Exception $e) {
            // If statement fails, log or ignore. Developer may need doctrine/dbal and use change() instead.
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            // Revert to VARCHAR(255) (best-effort). If original type was different, adjust as needed.
            DB::statement("ALTER TABLE `finance_pengajuan_dana` MODIFY `bukti_transaksi` VARCHAR(255) NULL");
        } catch (\Exception $e) {
            // ignore
        }
    }
};
