<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asesmen gigi: odontogram and Status Intra Oral are picked by clicking instead of drawn on the scanned form.
 * odontogram: {"16": ["car"], "26": ["mis"]}; intra_oral: {"oklusi": "Normal bite", ...}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_asesmen_gigi', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_asesmen_gigi', 'odontogram')) {
                $table->json('odontogram')->nullable()->after('ket_status_lokalis');
            }
            if (!Schema::hasColumn('erm_asesmen_gigi', 'intra_oral')) {
                $table->json('intra_oral')->nullable()->after('odontogram');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_asesmen_gigi', function (Blueprint $table) {
            foreach (['intra_oral', 'odontogram'] as $column) {
                if (Schema::hasColumn('erm_asesmen_gigi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
