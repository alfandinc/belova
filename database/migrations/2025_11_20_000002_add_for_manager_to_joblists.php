<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hrd_joblists')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                if (!Schema::hasColumn('hrd_joblists', 'for_manager')) {
                    $table->boolean('for_manager')->default(false)->after('all_divisions');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('hrd_joblists') && Schema::hasColumn('hrd_joblists', 'for_manager')) {
            Schema::table('hrd_joblists', function (Blueprint $table) {
                $table->dropColumn('for_manager');
            });
        }
    }
};
