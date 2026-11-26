<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hitung Harga Paket: master Biaya Lain, the alat / biaya lain used by each paket (erm_tindakan),
 * and the cost breakdown kept on a harga pengajuan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('erm_biaya_lain')) {
            Schema::create('erm_biaya_lain', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 100);
                $table->decimal('nominal', 15, 2)->default(0);
                $table->string('keterangan', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            $now = now();
            DB::table('erm_biaya_lain')->insert(array_map(fn ($r) => $r + ['nominal' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], [
                ['nama' => 'Linen & Laundry', 'keterangan' => 'Biaya laundry sebulan ÷ jumlah tindakan sebulan'],
                ['nama' => 'Sterilisasi', 'keterangan' => 'Pouch steril, cairan disinfektan, autoclave'],
                ['nama' => 'Listrik & AC', 'keterangan' => 'Tagihan listrik sebulan ÷ jumlah tindakan sebulan'],
                ['nama' => 'Ruangan', 'keterangan' => 'Sewa ruang tindakan sebulan ÷ jumlah tindakan sebulan'],
                ['nama' => 'Limbah Medis', 'keterangan' => 'Biaya jasa limbah sebulan ÷ jumlah tindakan sebulan'],
            ]));
        }

        if (!Schema::hasTable('erm_tindakan_alat')) {
            Schema::create('erm_tindakan_alat', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
                $table->foreignId('barang_id')->constrained('inv_barang')->cascadeOnDelete();
                $table->decimal('qty', 8, 2)->default(1);
                $table->timestamps();
                $table->unique(['tindakan_id', 'barang_id']);
            });
        }

        if (!Schema::hasTable('erm_tindakan_biaya_lain')) {
            Schema::create('erm_tindakan_biaya_lain', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
                $table->foreignId('biaya_lain_id')->constrained('erm_biaya_lain')->cascadeOnDelete();
                $table->decimal('nominal', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['tindakan_id', 'biaya_lain_id']);
            });
        }

        Schema::table('erm_tindakan_harga_pengajuan', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_tindakan_harga_pengajuan', 'biaya_jasa')) {
                $table->decimal('biaya_jasa', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('erm_tindakan_harga_pengajuan', 'biaya_alat')) {
                $table->decimal('biaya_alat', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('erm_tindakan_harga_pengajuan', 'biaya_lain')) {
                $table->decimal('biaya_lain', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('erm_tindakan_harga_pengajuan', 'rincian')) {
                $table->json('rincian')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('erm_tindakan_harga_pengajuan', function (Blueprint $table) {
            foreach (['rincian', 'biaya_lain', 'biaya_alat', 'biaya_jasa'] as $col) {
                if (Schema::hasColumn('erm_tindakan_harga_pengajuan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('erm_tindakan_biaya_lain');
        Schema::dropIfExists('erm_tindakan_alat');
        Schema::dropIfExists('erm_biaya_lain');
    }
};
