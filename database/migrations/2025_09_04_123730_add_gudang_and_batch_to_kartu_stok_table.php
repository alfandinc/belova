<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            // Tambah kolom batch jika belum ada
            if (!Schema::hasColumn('erm_kartu_stok', 'batch')) {
                $table->string('batch')->nullable()->after('qty');
            }
            
            // Tambah kolom keterangan jika belum ada
            if (!Schema::hasColumn('erm_kartu_stok', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('ref_id');
            }

            // Tambah index jika belum ada
            if (!Schema::hasIndex('erm_kartu_stok', 'erm_kartu_stok_obat_id_gudang_id_batch_index')) {
                $table->index(['obat_id', 'gudang_id', 'batch']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            // Hapus kolom batch jika ada
            if (Schema::hasColumn('erm_kartu_stok', 'batch')) {
                $table->dropColumn('batch');
            }
            
            // Hapus kolom keterangan jika ada
            if (Schema::hasColumn('erm_kartu_stok', 'keterangan')) {
                $table->dropColumn('keterangan');
            }

            // Hapus index jika ada
            if (Schema::hasIndex('erm_kartu_stok', 'erm_kartu_stok_obat_id_gudang_id_batch_index')) {
                $table->dropIndex(['obat_id', 'gudang_id', 'batch']);
            }
        });
    }
};
