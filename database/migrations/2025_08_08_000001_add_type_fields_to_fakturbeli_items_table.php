<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeFieldsToFakturbeliItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_fakturbeli_items', function (Blueprint $table) {
            $table->string('diskon_type')->default('nominal')->after('diskon');
            $table->string('tax_type')->default('nominal')->after('tax');
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
            $table->dropColumn('diskon_type');
            $table->dropColumn('tax_type');
        });
    }
}
