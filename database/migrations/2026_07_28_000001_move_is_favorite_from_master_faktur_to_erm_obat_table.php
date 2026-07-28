<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('is_generik');
        });

        $favoriteObatIds = DB::table('erm_master_faktur')
            ->where('is_favorite', true)
            ->distinct()
            ->pluck('obat_id');

        if ($favoriteObatIds->isNotEmpty()) {
            DB::table('erm_obat')
                ->whereIn('id', $favoriteObatIds)
                ->update(['is_favorite' => true]);
        }

        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('notes');
        });

        $favoriteObatIds = DB::table('erm_obat')
            ->where('is_favorite', true)
            ->pluck('id');

        if ($favoriteObatIds->isNotEmpty()) {
            DB::table('erm_master_faktur')
                ->whereIn('obat_id', $favoriteObatIds)
                ->update(['is_favorite' => true]);
        }

        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropColumn('is_favorite');
        });
    }
};