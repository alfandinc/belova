<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * satuan      = unit of `dosis` (kekuatan per 1 satuan stok), unchanged meaning for racikan.
 * satuan_stok = unit stock, HPP and harga jual are counted in.
 *
 * Also normalizes legacy satuan spellings (Gram/g, Ml/mL, iu, kapsul, plaboth, ...).
 * down() only drops the new column; the spelling clean-up is not reverted.
 */
return new class extends Migration
{
    private const ALIASES = [
        'mg' => 'mg', 'mcg' => 'mcg', 'g' => 'g', 'gr' => 'g', 'gram' => 'g', 'ml' => 'mL', 'iu' => 'IU',
        'tablet' => 'Tablet', 'tab' => 'Tablet', 'kapsul' => 'Kapsul', 'kaplet' => 'Kaplet', 'botol' => 'Botol',
        'plaboth' => 'Botol', 'tube' => 'Tube', 'pot' => 'Pot', 'ampul' => 'Ampul', 'vial' => 'Vial',
        'sachet' => 'Sachet', 'strip' => 'Strip', 'pcs' => 'Pcs', 'pack' => 'Pack', 'box' => 'Box',
        'softbag' => 'Softbag', 'pen' => 'Pen', 'pump' => 'Pump',
    ];

    private const STOK_UNITS = [
        'Tablet', 'Kapsul', 'Kaplet', 'Botol', 'Tube', 'Pot', 'Ampul', 'Vial', 'Sachet', 'Strip',
        'Pcs', 'Pack', 'Box', 'Softbag', 'Pen', 'Pump',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('erm_obat', 'satuan_stok')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->string('satuan_stok', 50)->nullable()->after('satuan');
            });
        }

        DB::transaction(function () {
            DB::table('erm_obat')->whereRaw("TRIM(COALESCE(satuan, '')) = ''")->update(['satuan' => null]);

            foreach (self::ALIASES as $alias => $canonical) {
                DB::table('erm_obat')
                    ->whereRaw('LOWER(TRIM(satuan)) = ?', [$alias])
                    ->update(['satuan' => $canonical]);
            }

            // Items whose satuan already is a packaging unit: that is their stok unit too.
            DB::table('erm_obat')
                ->whereNull('satuan_stok')
                ->whereIn('satuan', self::STOK_UNITS)
                ->update(['satuan_stok' => DB::raw('satuan')]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_obat', 'satuan_stok')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->dropColumn('satuan_stok');
            });
        }
    }
};
