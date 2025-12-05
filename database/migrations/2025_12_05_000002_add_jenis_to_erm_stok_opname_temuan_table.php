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
            $table->string('jenis', 20)->default('kurang')->after('qty')->comment('jenis temuan: kurang atau lebih');
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
            $table->dropColumn('jenis');
        });
    }
};
