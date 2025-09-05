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
            // Tambah kolom gudang_id jika belum ada
            if (!Schema::hasColumn('erm_kartu_stok', 'gudang_id')) {
                $table->unsignedBigInteger('gudang_id')->nullable()->after('obat_id');
            }
            
            // Tambah kolom batch jika belum ada
            if (!Schema::hasColumn('erm_kartu_stok', 'batch')) {
                $table->string('batch')->nullable()->after('gudang_id');
            }
            
            // Tambah kolom keterangan jika belum ada
            if (!Schema::hasColumn('erm_kartu_stok', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('ref_id');
            }
        });

        // Tambah foreign key untuk gudang_id
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            if (Schema::hasColumn('erm_kartu_stok', 'gudang_id')) {
                $table->foreign('gudang_id')->references('id')->on('erm_gudang');
            }
        });

        // Tambah index composite untuk performance
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            if (!Schema::hasIndex('erm_kartu_stok', 'idx_kartu_stok_obat_gudang_batch')) {
                $table->index(['obat_id', 'gudang_id', 'batch'], 'idx_kartu_stok_obat_gudang_batch');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('erm_kartu_stok', function (Blueprint $table) {
            // Hapus index jika ada
            if (Schema::hasIndex('erm_kartu_stok', 'idx_kartu_stok_obat_gudang_batch')) {
                $table->dropIndex('idx_kartu_stok_obat_gudang_batch');
            }
            
            // Hapus foreign key
            $table->dropForeign(['gudang_id']);
            
            // Hapus kolom-kolom yang ditambahkan
            if (Schema::hasColumn('erm_kartu_stok', 'gudang_id')) {
                $table->dropColumn('gudang_id');
            }
            
            if (Schema::hasColumn('erm_kartu_stok', 'batch')) {
                $table->dropColumn('batch');
            }
            
            if (Schema::hasColumn('erm_kartu_stok', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};
