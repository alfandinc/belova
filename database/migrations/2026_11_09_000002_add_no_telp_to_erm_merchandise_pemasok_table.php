<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('erm_merchandise_pemasok') && !Schema::hasColumn('erm_merchandise_pemasok', 'no_telp')) {
            Schema::table('erm_merchandise_pemasok', function (Blueprint $table) {
                $table->string('no_telp', 30)->nullable()->after('nama');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('erm_merchandise_pemasok', 'no_telp')) {
            Schema::table('erm_merchandise_pemasok', function (Blueprint $table) {
                $table->dropColumn('no_telp');
            });
        }
    }
};
