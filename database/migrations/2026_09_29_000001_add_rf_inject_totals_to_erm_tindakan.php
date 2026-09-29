<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_tindakan', 'total_rf')) {
                $table->unsignedInteger('total_rf')->nullable()->after('multi_visit_total');
            }

            if (!Schema::hasColumn('erm_tindakan', 'total_inject')) {
                $table->unsignedInteger('total_inject')->nullable()->after('total_rf');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_tindakan', function (Blueprint $table) {
            if (Schema::hasColumn('erm_tindakan', 'total_inject')) {
                $table->dropColumn('total_inject');
            }

            if (Schema::hasColumn('erm_tindakan', 'total_rf')) {
                $table->dropColumn('total_rf');
            }
        });
    }
};
