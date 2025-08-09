<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDimintaToErmFakturbeliItemsTable extends Migration
{
    public function up()
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->integer('diminta')->nullable()->after('qty');
        });
    }

    public function down()
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->dropColumn('diminta');
        });
    }
}
