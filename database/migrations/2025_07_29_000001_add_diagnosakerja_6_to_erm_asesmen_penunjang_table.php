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
        Schema::table('erm_asesmen_penunjang', function (Blueprint $table) {
            $table->text('diagnosakerja_6')->nullable()->after('diagnosakerja_5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_asesmen_penunjang', function (Blueprint $table) {
            $table->dropColumn('diagnosakerja_6');
        });
    }
};
