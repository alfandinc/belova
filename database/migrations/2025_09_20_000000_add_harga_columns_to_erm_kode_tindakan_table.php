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
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->decimal('harga_jual', 12, 2)->nullable()->after('harga_jasmed');
            $table->decimal('harga_bottom', 12, 2)->nullable()->after('harga_jual');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_kode_tindakan', function (Blueprint $table) {
            $table->dropColumn(['harga_jual', 'harga_bottom']);
        });
    }
};
