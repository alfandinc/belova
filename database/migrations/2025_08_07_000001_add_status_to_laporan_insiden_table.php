<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->string('status')->default('Dibuat')->after('grading_resiko');
        });
    }

    public function down()
    {
        Schema::table('laporan_insiden', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
