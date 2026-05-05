<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('attendance_rekap', function (Blueprint $table) {
            $table->boolean('ignore_terlambat')->default(false)->after('work_hour');
            $table->boolean('ignore_overtime')->default(false)->after('ignore_terlambat');
        });
    }

    public function down()
    {
        Schema::table('attendance_rekap', function (Blueprint $table) {
            $table->dropColumn(['ignore_terlambat', 'ignore_overtime']);
        });
    }
};