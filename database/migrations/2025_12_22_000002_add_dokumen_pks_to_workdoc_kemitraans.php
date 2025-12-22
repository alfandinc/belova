<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workdoc_kemitraans', function (Blueprint $table) {
            $table->string('dokumen_pks')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('workdoc_kemitraans', function (Blueprint $table) {
            $table->dropColumn('dokumen_pks');
        });
    }
};
