<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->string('gambar_referensi')->nullable()->after('catatan');
        });
    }

    public function down()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->dropColumn('gambar_referensi');
        });
    }
};
