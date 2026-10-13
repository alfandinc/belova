<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erm_visitations', function (Blueprint $table) {
            // "Today's patients" (dashboard widgets) filter on one date and group by dokter
            $table->index(['tanggal_visitation', 'dokter_id'], 'erm_visitations_tanggal_dokter_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erm_visitations', function (Blueprint $table) {
            $table->dropIndex('erm_visitations_tanggal_dokter_index');
        });
    }
};
