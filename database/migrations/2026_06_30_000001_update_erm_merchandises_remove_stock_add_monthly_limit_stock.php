<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('erm_merchandises', function (Blueprint $table) {
            if (Schema::hasColumn('erm_merchandises', 'stock')) {
                $table->dropColumn('stock');
            }

            if (!Schema::hasColumn('erm_merchandises', 'monthly_limit_stock')) {
                $table->integer('monthly_limit_stock')->nullable()->after('price');
            }
        });
    }

    public function down()
    {
        Schema::table('erm_merchandises', function (Blueprint $table) {
            if (Schema::hasColumn('erm_merchandises', 'monthly_limit_stock')) {
                $table->dropColumn('monthly_limit_stock');
            }

            if (!Schema::hasColumn('erm_merchandises', 'stock')) {
                $table->integer('stock')->default(0)->after('price');
            }
        });
    }
};
