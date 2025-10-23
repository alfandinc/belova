<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->unsignedBigInteger('principal_id')->nullable()->after('pemasok_id');
            $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->dropForeign(['principal_id']);
            $table->dropColumn('principal_id');
        });
    }
};
