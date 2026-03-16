<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->string('remote_wa_id')->nullable()->after('message_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropIndex(['remote_wa_id']);
            $table->dropColumn('remote_wa_id');
        });
    }
};