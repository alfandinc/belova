<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An employee can work for more than one company. `perusahaan_list` holds all of them; `perusahaan` stays the
 * main company (other modules, e.g. the dashboard employee widget, read it as a single value).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->json('perusahaan_list')->nullable()->after('perusahaan');
        });

        // Existing employees: the list starts with their current (single) company
        DB::table('hrd_employee')->whereNotNull('perusahaan')->where('perusahaan', '<>', '')->orderBy('id')
            ->each(function ($row) {
                DB::table('hrd_employee')->where('id', $row->id)->update(['perusahaan_list' => json_encode([$row->perusahaan])]);
            });
    }

    public function down(): void
    {
        Schema::table('hrd_employee', function (Blueprint $t) {
            $t->dropColumn('perusahaan_list');
        });
    }
};
