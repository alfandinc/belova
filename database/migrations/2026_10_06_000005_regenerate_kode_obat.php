<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Regenerates every kode_obat as PREFIX-00001 per kategori (ID order) and makes it unique.
 * The previous code is kept in kode_obat_lama; down() restores it.
 * Also normalizes kategori spelling ("obat" -> "Obat", "" -> NULL). Items without kategori get no code.
 */
return new class extends Migration
{
    private const KODE_PREFIX = [
        'Obat' => 'OBT', 'Produk' => 'PRD', 'Racikan' => 'RCK',
        'Bhp' => 'BHP', 'Bhp Alat' => 'BHA', 'Lainnya' => 'LNN',
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('erm_obat', 'kode_obat_lama')) {
            Schema::table('erm_obat', function (Blueprint $table) {
                $table->string('kode_obat_lama', 191)->nullable()->after('kode_obat');
            });
        }

        DB::transaction(function () {
            DB::table('erm_obat')->whereRaw("TRIM(COALESCE(kategori, '')) = ''")->update(['kategori' => null]);
            foreach (array_keys(self::KODE_PREFIX) as $kategori) {
                DB::table('erm_obat')
                    ->whereRaw('LOWER(TRIM(kategori)) = ?', [strtolower($kategori)])
                    ->update(['kategori' => $kategori]);
            }

            DB::table('erm_obat')
                ->whereNull('kode_obat_lama')
                ->whereRaw("TRIM(COALESCE(kode_obat, '')) <> ''")
                ->update(['kode_obat_lama' => DB::raw('TRIM(kode_obat)')]);

            DB::table('erm_obat')->update(['kode_obat' => null]);

            foreach (self::KODE_PREFIX as $kategori => $prefix) {
                $ids = DB::table('erm_obat')->where('kategori', $kategori)->orderBy('id')->pluck('id');
                foreach ($ids as $i => $id) {
                    DB::table('erm_obat')->where('id', $id)->update([
                        'kode_obat' => sprintf('%s-%05d', $prefix, $i + 1),
                    ]);
                }
            }
        });

        Schema::table('erm_obat', function (Blueprint $table) {
            $table->unique('kode_obat', 'erm_obat_kode_obat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropUnique('erm_obat_kode_obat_unique');
        });

        DB::table('erm_obat')->update(['kode_obat' => DB::raw('kode_obat_lama')]);

        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropColumn('kode_obat_lama');
        });
    }
};
