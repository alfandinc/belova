<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_inform_consent', function (Blueprint $table) {
            $table->boolean('allow_post')->default(false)->after('riwayat_tindakan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_inform_consent', function (Blueprint $table) {
            $table->dropColumn('allow_post');
        });
    }
};
