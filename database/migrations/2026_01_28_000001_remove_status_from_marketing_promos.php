<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('marketing_promos') && Schema::hasColumn('marketing_promos', 'status')) {
            Schema::table('marketing_promos', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('marketing_promos') && !Schema::hasColumn('marketing_promos', 'status')) {
            Schema::table('marketing_promos', function (Blueprint $table) {
                $table->string('status')->default('inactive')->after('end_date');
            });
        }
    }
};
