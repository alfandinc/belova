<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('erm_merchandises', 'monthly_limit_stock')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->dropColumn('monthly_limit_stock');
            });
        }
    }

    /**
     * Restores the column only. The old limit values are not recoverable.
     */
    public function down()
    {
        if (!Schema::hasColumn('erm_merchandises', 'monthly_limit_stock')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->integer('monthly_limit_stock')->nullable()->after('price');
            });
        }
    }
};
