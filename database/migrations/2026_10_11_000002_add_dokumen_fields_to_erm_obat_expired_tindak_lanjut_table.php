<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tindak lanjut stok expired as a document:
 * - Dimusnahkan: Berita Acara Pemusnahan (nomor, tanggal, metode, 2 saksi), printable.
 * - Diretur: linked to the Retur Pembelian (faktur retur) it created; stok only leaves the
 *   gudang expired when that retur is approved (status menunggu_retur -> selesai).
 * Existing rows were completed immediately, so they default to status 'selesai'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_obat_expired_tindak_lanjut', function (Blueprint $t) {
            $t->string('status', 20)->default('selesai')->after('tindak_lanjut');
            $t->string('nomor_dokumen', 50)->nullable()->after('status');
            $t->date('tanggal')->nullable()->after('nomor_dokumen');
            $t->string('metode', 100)->nullable()->after('tanggal');
            $t->string('saksi_1', 150)->nullable()->after('metode');
            $t->string('saksi_2', 150)->nullable()->after('saksi_1');
            $t->foreignId('fakturretur_id')->nullable()->after('saksi_2')
                ->constrained('erm_fakturretur')->nullOnDelete();

            $t->index('nomor_dokumen');
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('erm_obat_expired_tindak_lanjut', function (Blueprint $t) {
            $t->dropForeign(['fakturretur_id']);
            $t->dropIndex(['nomor_dokumen']);
            $t->dropIndex(['status']);
            $t->dropColumn(['status', 'nomor_dokumen', 'tanggal', 'metode', 'saksi_1', 'saksi_2', 'fakturretur_id']);
        });
    }
};
