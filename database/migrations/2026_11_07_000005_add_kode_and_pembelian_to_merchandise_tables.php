<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('erm_merchandises', 'kode')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->string('kode', 20)->nullable()->unique()->after('id');
            });
        }

        DB::table('erm_merchandises')->whereNull('kode')->orderBy('id')->get(['id'])->each(function ($row) {
            DB::table('erm_merchandises')->where('id', $row->id)->update([
                'kode' => 'MRC-' . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
            ]);
        });

        if (!Schema::hasTable('erm_merchandise_pemasok')) {
            Schema::create('erm_merchandise_pemasok', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('erm_merchandise_pembelian')) {
            Schema::create('erm_merchandise_pembelian', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->nullable()->unique();
                $table->foreignId('merchandise_id')->constrained('erm_merchandises')->restrictOnDelete();
                $table->string('sumber', 20)->default('pembelian');
                $table->date('tanggal_beli');
                $table->foreignId('pemasok_id')->nullable()->constrained('erm_merchandise_pemasok')->restrictOnDelete();
                $table->string('no_nota', 100)->nullable();
                $table->string('foto_nota')->nullable();
                $table->unsignedInteger('qty');
                $table->decimal('harga_satuan', 14, 2)->nullable();
                $table->decimal('ongkir', 14, 2)->default(0);
                $table->decimal('biaya_lain', 14, 2)->default(0);
                $table->text('catatan')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['merchandise_id', 'tanggal_beli']);
            });
        }

        Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
            if (!Schema::hasColumn('erm_merchandise_kartu_stok', 'sumber')) {
                $table->string('sumber', 20)->nullable()->after('type');
            }
            if (!Schema::hasColumn('erm_merchandise_kartu_stok', 'pembelian_id')) {
                $table->unsignedBigInteger('pembelian_id')->nullable()->after('sumber')->index();
            }
        });

        // Stock that came in before purchases were recorded. Cancellations and adjustments are not new stock.
        DB::table('erm_merchandise_kartu_stok')
            ->where('type', 'in')
            ->whereNull('sumber')
            ->where('notes', 'not like', 'Pembatalan merchandise%')
            ->where('notes', 'not like', 'Penyesuaian merchandise%')
            ->update(['sumber' => 'stok_awal']);
    }

    public function down()
    {
        Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
            if (Schema::hasColumn('erm_merchandise_kartu_stok', 'pembelian_id')) {
                $table->dropIndex(['pembelian_id']);
                $table->dropColumn('pembelian_id');
            }
        });

        Schema::table('erm_merchandise_kartu_stok', function (Blueprint $table) {
            if (Schema::hasColumn('erm_merchandise_kartu_stok', 'sumber')) {
                $table->dropColumn('sumber');
            }
        });

        Schema::dropIfExists('erm_merchandise_pembelian');
        Schema::dropIfExists('erm_merchandise_pemasok');

        if (Schema::hasColumn('erm_merchandises', 'kode')) {
            Schema::table('erm_merchandises', function (Blueprint $table) {
                $table->dropUnique(['kode']);
                $table->dropColumn('kode');
            });
        }
    }
};
