<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalAmountToErmFakturbeliItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->decimal('total_amount', 20, 2)->nullable()->after('diminta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
}
