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
        Schema::table('erm_resepdetail', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->after('catatan_dokter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_resepdetail', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
