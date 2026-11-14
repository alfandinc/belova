<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Official Kemendagri code per wilayah (e.g. 33.72.01.1001), next to the BPS-based id. Filled by
 * `php artisan area:impor-kemendagri`. For SatuSehat / other systems that expect Kemendagri codes.
 */
return new class extends Migration
{
    private const TABLES = ['area_provinces', 'area_regencies', 'area_districts', 'area_villages'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'kode_kemendagri')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('kode_kemendagri', 13)->nullable()->after('name')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'kode_kemendagri')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropIndex($table . '_kode_kemendagri_index');
                    $t->dropColumn('kode_kemendagri');
                });
            }
        }
    }
};
