<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeNamaItemToTextOnFinancePengajuanDanaItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: altering column types with ->change() requires doctrine/dbal package.
     * If doctrine/dbal is not available in your environment, create a new TEXT column,
     * copy data, then drop the old column manually.
     *
     * @return void
     */
    public function up()
    {
        // Prefer changing the existing column to TEXT. This uses ->change() which
        // requires doctrine/dbal to be installed.
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            // make sure column exists; change to text
            $table->text('nama_item')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            // revert to varchar(255)
            $table->string('nama_item', 255)->nullable()->change();
        });
    }
}
