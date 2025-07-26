<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('marketing_follow_ups', function (Blueprint $table) {
            $table->json('kategori')->change();
        });
    }

    public function down()
    {
        Schema::table('marketing_follow_ups', function (Blueprint $table) {
            $table->string('kategori')->change();
        });
    }
};
