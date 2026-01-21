<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->string('pasien_id')->nullable()->index()->after('raw');
        });
    }

    public function down()
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropColumn('pasien_id');
        });
    }
};
