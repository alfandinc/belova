<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('running_wa_scheduled_messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('message')->comment('Absolute path to generated ticket image');
        });
    }

    public function down()
    {
        Schema::table('running_wa_scheduled_messages', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
