<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_riwayat_tindakan', 'total_rf')) {
                $table->unsignedInteger('total_rf')->nullable()->after('multi_visit_usage_id');
            }

            if (!Schema::hasColumn('erm_riwayat_tindakan', 'rf_used')) {
                $table->unsignedInteger('rf_used')->nullable()->after('total_rf');
            }

            if (!Schema::hasColumn('erm_riwayat_tindakan', 'total_inject')) {
                $table->unsignedInteger('total_inject')->nullable()->after('rf_used');
            }

            if (!Schema::hasColumn('erm_riwayat_tindakan', 'inject_used')) {
                $table->unsignedInteger('inject_used')->nullable()->after('total_inject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_riwayat_tindakan', function (Blueprint $table) {
            foreach (['inject_used', 'total_inject', 'rf_used', 'total_rf'] as $column) {
                if (Schema::hasColumn('erm_riwayat_tindakan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
