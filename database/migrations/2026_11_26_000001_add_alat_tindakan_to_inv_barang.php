<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alat tindakan: inventory items whose cost is charged into the price of a paket tindakan
 * (Hitung Harga Paket). harga_perolehan / tanggal_perolehan are only needed when the alat has
 * no Pembelian record (old alat): a fake pembelian would add stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_barang', function (Blueprint $table) {
            if (!Schema::hasColumn('inv_barang', 'is_alat_tindakan')) {
                $table->boolean('is_alat_tindakan')->default(false);
            }
            if (!Schema::hasColumn('inv_barang', 'harga_perolehan')) {
                $table->decimal('harga_perolehan', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('inv_barang', 'tanggal_perolehan')) {
                $table->date('tanggal_perolehan')->nullable();
            }
            if (!Schema::hasColumn('inv_barang', 'umur_pakai_tahun')) {
                $table->unsignedTinyInteger('umur_pakai_tahun')->nullable();
            }
            if (!Schema::hasColumn('inv_barang', 'target_pakai_per_bulan')) {
                $table->unsignedInteger('target_pakai_per_bulan')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inv_barang', function (Blueprint $table) {
            foreach (['target_pakai_per_bulan', 'umur_pakai_tahun', 'tanggal_perolehan', 'harga_perolehan', 'is_alat_tindakan'] as $col) {
                if (Schema::hasColumn('inv_barang', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
