<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('wa_scheduled_messages', function (Blueprint $table) {
            $table->string('pasien_id')->nullable()->after('client_id');
            $table->index('pasien_id');
        });
    }

    public function down()
    {
        Schema::table('wa_scheduled_messages', function (Blueprint $table) {
            $table->dropIndex(['pasien_id']);
            $table->dropColumn('pasien_id');
        });
    }
};