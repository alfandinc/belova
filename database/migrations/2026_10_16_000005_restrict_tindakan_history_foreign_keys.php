<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient history (riwayat tindakan and its obat, SPK, inform consent) used to be deleted together with the
 * paket tindakan / tindakan it points at. The controllers already refuse such deletes; the database now does too.
 */
return new class extends Migration
{
    /** [table, column, referenced table] */
    private const KEYS = [
        ['erm_riwayat_tindakan', 'tindakan_id', 'erm_tindakan'],
        ['erm_spk', 'tindakan_id', 'erm_tindakan'],
        ['erm_inform_consent', 'tindakan_id', 'erm_tindakan'],
        ['erm_riwayat_tindakan_obat', 'kode_tindakan_id', 'erm_kode_tindakan'],
        ['erm_spk_tindakan_items', 'kode_tindakan_id', 'erm_kode_tindakan'],
    ];

    public function up(): void
    {
        $this->setOnDelete('restrict');
    }

    public function down(): void
    {
        $this->setOnDelete('cascade');
    }

    private function setOnDelete(string $rule): void
    {
        foreach (self::KEYS as [$table, $column, $references]) {
            Schema::table($table, function (Blueprint $t) use ($column, $references, $rule) {
                $t->dropForeign([$column]);
                $t->foreign($column)->references('id')->on($references)->onDelete($rule);
            });
        }
    }
};
