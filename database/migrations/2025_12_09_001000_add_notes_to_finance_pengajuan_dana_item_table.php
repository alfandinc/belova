<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToFinancePengajuanDanaItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_pengajuan_dana_item', 'notes')) {
                $table->text('notes')->nullable()->after('employee_id');
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
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            if (Schema::hasColumn('finance_pengajuan_dana_item', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
}
