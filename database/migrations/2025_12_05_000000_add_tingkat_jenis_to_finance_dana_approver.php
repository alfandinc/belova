<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTingkatJenisToFinanceDanaApprover extends Migration
{
    public function up()
    {
        Schema::table('finance_dana_approver', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_dana_approver', 'tingkat')) {
                $table->integer('tingkat')->default(1)->after('jabatan');
            }
            if (!Schema::hasColumn('finance_dana_approver', 'jenis')) {
                // Store which jenis_pengajuan this approver handles; null/empty = all
                $table->string('jenis')->nullable()->after('tingkat');
            }
        });
    }

    public function down()
    {
        Schema::table('finance_dana_approver', function (Blueprint $table) {
            if (Schema::hasColumn('finance_dana_approver', 'jenis')) {
                $table->dropColumn('jenis');
            }
            if (Schema::hasColumn('finance_dana_approver', 'tingkat')) {
                $table->dropColumn('tingkat');
            }
        });
    }
}
