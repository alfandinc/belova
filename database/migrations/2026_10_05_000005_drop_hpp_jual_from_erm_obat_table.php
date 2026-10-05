<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backup nilai lama: storage/app/backups/erm_obat_hpp_jual_20261005.csv
     */
    public function up(): void
    {
        if (Schema::hasColumn('erm_obat', 'hpp_jual')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->dropColumn('hpp_jual');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('erm_obat', 'hpp_jual')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->decimal('hpp_jual', 15, 2)->nullable()->after('hpp');
            });
        }
    }
};
