<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('erm_lab_test', function (Blueprint $table) {
            $table->string('available', 10)->default('yes')->after('deskripsi');
        });
    }

    public function down()
    {
        Schema::table('erm_lab_test', function (Blueprint $table) {
            $table->dropColumn('available');
        });
    }
};
