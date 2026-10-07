<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Pembelian holds one price per obat + pemasok. The app already refuses duplicates
 * (MasterFakturController, MasterPembelianService); this makes the database enforce it too.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('erm_master_faktur')
            ->select('obat_id', 'pemasok_id', DB::raw('COUNT(*) as c'))
            ->groupBy('obat_id', 'pemasok_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'erm_master_faktur has duplicate obat + pemasok rows, merge them first: '
                . $duplicates->map(fn ($d) => "obat {$d->obat_id} / pemasok {$d->pemasok_id} ({$d->c}x)")->implode(', ')
            );
        }

        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->unique(['obat_id', 'pemasok_id'], 'erm_master_faktur_obat_pemasok_unique');
        });
    }

    public function down(): void
    {
        Schema::table('erm_master_faktur', function (Blueprint $table) {
            $table->dropUnique('erm_master_faktur_obat_pemasok_unique');
        });
    }
};
