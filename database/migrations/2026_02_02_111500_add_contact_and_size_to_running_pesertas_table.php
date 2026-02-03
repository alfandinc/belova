<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (!Schema::hasColumn('running_pesertas', 'no_hp')) {
                $table->string('no_hp')->nullable()->after('unique_code');
            }
            if (!Schema::hasColumn('running_pesertas', 'email')) {
                $table->string('email')->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('running_pesertas', 'ukuran_kaos')) {
                $table->string('ukuran_kaos')->nullable()->after('email');
            }
        });
    }

    public function down()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (Schema::hasColumn('running_pesertas', 'ukuran_kaos')) {
                $table->dropColumn('ukuran_kaos');
            }
            if (Schema::hasColumn('running_pesertas', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('running_pesertas', 'no_hp')) {
                $table->dropColumn('no_hp');
            }
        });
    }
};
