<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->string('visitation_id')->nullable()->after('remote_wa_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropIndex(['visitation_id']);
            $table->dropColumn('visitation_id');
        });
    }
};