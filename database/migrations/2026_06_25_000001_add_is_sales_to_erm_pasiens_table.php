<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSalesToErmPasiensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->boolean('is_sales')->default(0)->after('status_review');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            if (Schema::hasColumn('erm_pasiens', 'is_sales')) {
                $table->dropColumn('is_sales');
            }
        });
    }
}
