<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->string('no_darurat', 50)->nullable()->after('no_induk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->dropColumn('no_darurat');
        });
    }
};
