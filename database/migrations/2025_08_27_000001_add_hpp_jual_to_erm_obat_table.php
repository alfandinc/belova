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
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->decimal('hpp_jual', 15, 2)->nullable()->after('hpp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropColumn('hpp_jual');
        });
    }
};
