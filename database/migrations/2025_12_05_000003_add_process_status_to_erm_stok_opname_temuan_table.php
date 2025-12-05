<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_stok_opname_temuan', function (Blueprint $table) {
            $table->tinyInteger('process_status')->default(0)->after('jenis')->comment('0=not processed,1=processed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_stok_opname_temuan', function (Blueprint $table) {
            $table->dropColumn('process_status');
        });
    }
};
