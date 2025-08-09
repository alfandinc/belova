<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToErmFakturbeliTable extends Migration
{
    public function up()
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->string('status')->default('diminta')->after('total');
        });
    }

    public function down()
    {
        Schema::table('erm_fakturbeli', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
