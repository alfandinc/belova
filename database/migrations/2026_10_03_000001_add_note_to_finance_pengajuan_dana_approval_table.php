<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToFinancePengajuanDanaApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('finance_pengajuan_dana_approval', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_pengajuan_dana_approval', 'note')) {
                // reason given when an approver declines (alasan penolakan)
                $table->text('note')->nullable()->after('tanggal_approve');
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
        Schema::table('finance_pengajuan_dana_approval', function (Blueprint $table) {
            if (Schema::hasColumn('finance_pengajuan_dana_approval', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
}
