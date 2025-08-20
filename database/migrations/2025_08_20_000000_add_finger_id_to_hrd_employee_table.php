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
            $table->string('finger_id')->nullable()->after('perusahaan');
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
            $table->dropColumn('finger_id');
        });
    }
};
