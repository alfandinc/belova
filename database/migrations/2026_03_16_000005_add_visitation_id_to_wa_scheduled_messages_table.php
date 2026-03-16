<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wa_scheduled_messages', function (Blueprint $table) {
            $table->string('visitation_id')->nullable()->after('pasien_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wa_scheduled_messages', function (Blueprint $table) {
            $table->dropIndex(['visitation_id']);
            $table->dropColumn('visitation_id');
        });
    }
};