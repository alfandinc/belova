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
        // Add employee_id to finance_pengajuan_dana_item if not present
        if (!Schema::hasColumn('finance_pengajuan_dana_item', 'employee_id')) {
            Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
                // use unsignedBigInteger to match typical id columns
                $table->unsignedBigInteger('employee_id')->nullable()->after('pengajuan_id');
                // add foreign key to HRD employee table if that table exists
                if (Schema::hasTable('hrd_employee')) {
                    $table->foreign('employee_id')
                        ->references('id')
                        ->on('hrd_employee')
                        ->onDelete('set null');
                }
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
        if (Schema::hasColumn('finance_pengajuan_dana_item', 'employee_id')) {
            Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
                // drop foreign key first if exists
                // Laravel requires the constraint name; we'll attempt to drop by column name which works on many DB engines
                try {
                    $table->dropForeign(['employee_id']);
                } catch (\Exception $e) {
                    // ignore if constraint does not exist
                }

                try {
                    $table->dropColumn('employee_id');
                } catch (\Exception $e) {
                    // ignore if column removal fails
                }
            });
        }
    }
};
