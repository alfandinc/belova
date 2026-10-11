<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kartu stok is read by period (kartu stok page, forecast) and by source document (retur to the
 * original batch, opname already applied, transfer print). Indexes only; no data changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_kartu_stok', function (Blueprint $t) {
            if (!Schema::hasIndex('erm_kartu_stok', 'erm_kartu_stok_obat_gudang_tanggal_index')) {
                $t->index(['obat_id', 'gudang_id', 'tanggal'], 'erm_kartu_stok_obat_gudang_tanggal_index');
            }
            if (!Schema::hasIndex('erm_kartu_stok', 'erm_kartu_stok_ref_index')) {
                $t->index(['ref_type', 'ref_id'], 'erm_kartu_stok_ref_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_kartu_stok', function (Blueprint $t) {
            $t->dropIndex('erm_kartu_stok_obat_gudang_tanggal_index');
            $t->dropIndex('erm_kartu_stok_ref_index');
        });
    }
};
