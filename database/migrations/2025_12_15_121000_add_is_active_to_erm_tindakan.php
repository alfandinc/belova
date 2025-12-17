<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToErmTindakan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_tindakan', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('diskon_active');
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
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
}
