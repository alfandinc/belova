<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('hrd_joblists', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('hrd_joblists', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
