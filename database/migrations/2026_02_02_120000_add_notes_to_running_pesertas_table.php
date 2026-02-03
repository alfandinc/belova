<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (!Schema::hasColumn('running_pesertas', 'notes')) {
                $table->text('notes')->nullable()->after('ukuran_kaos');
            }
        });
    }

    public function down()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (Schema::hasColumn('running_pesertas', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
