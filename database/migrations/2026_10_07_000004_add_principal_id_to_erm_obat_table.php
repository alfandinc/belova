<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The principal (manufacturer / brand owner) belongs to the obat, not to the pemasok it is bought from,
 * so it moves to erm_obat.principal_id and is entered in Master Obat.
 *
 * Filled from Master Pembelian (latest row with a principal), then from the latest faktur beli item
 * for obat that are still empty. erm_master_faktur.principal_id is kept for now (no longer written).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->unsignedBigInteger('principal_id')->nullable()->after('is_generik');
            // Same rule as the other principal keys: a principal in use cannot be deleted (merge instead)
            $table->foreign('principal_id')->references('id')->on('erm_principals')->onDelete('restrict');
        });

        $sources = [
            DB::table('erm_master_faktur')->whereNotNull('principal_id')
                ->orderByDesc('updated_at')->orderByDesc('id')->get(['obat_id', 'principal_id']),
            DB::table('erm_fakturbeli_items')->whereNotNull('principal_id')
                ->orderByDesc('id')->get(['obat_id', 'principal_id']),
        ];

        $principalByObat = [];
        foreach ($sources as $rows) {
            foreach ($rows as $row) {
                // First hit per obat wins: Master Pembelian before faktur, newest first
                $principalByObat[$row->obat_id] ??= $row->principal_id;
            }
        }

        foreach ($principalByObat as $obatId => $principalId) {
            DB::table('erm_obat')->where('id', $obatId)->whereNull('principal_id')->update(['principal_id' => $principalId]);
        }
    }

    public function down(): void
    {
        Schema::table('erm_obat', function (Blueprint $table) {
            $table->dropForeign(['principal_id']);
            $table->dropColumn('principal_id');
        });
    }
};
