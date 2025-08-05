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
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->nullable()->after('status_aktif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
};
