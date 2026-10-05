<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Realisasi of a paid pengajuan dana: actual amount spent per item (item.realisasi) and one
 * realisasi row per pengajuan (sisa = total dibayar - total realisasi, nota files, bukti pengembalian,
 * status selesai | menunggu_konfirmasi).
 *
 * Safe to run on any system: the table and column are only created when missing
 * (some systems already have them from an earlier, removed migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('finance_pengajuan_dana_item', 'realisasi')) {
            Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
                $table->decimal('realisasi', 15, 2)->nullable()->after('harga_total_snapshot');
            });
        }

        if (!Schema::hasTable('finance_pengajuan_dana_realisasi')) {
            Schema::create('finance_pengajuan_dana_realisasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pengajuan_id')->unique();
                $table->decimal('total_realisasi', 15, 2)->default(0);
                $table->decimal('sisa', 15, 2)->default(0);
                $table->text('note')->nullable();
                $table->text('nota')->nullable(); // JSON array of file paths
                $table->string('status', 32);
                $table->string('bukti_pengembalian')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable(); // users.id (no FK: user id types differ between systems)
                $table->dateTime('submitted_at')->nullable();
                $table->unsignedBigInteger('confirmed_by')->nullable(); // users.id
                $table->dateTime('confirmed_at')->nullable();
                $table->timestamps();

                $table->foreign('pengajuan_id')->references('id')->on('finance_pengajuan_dana')->cascadeOnDelete();
                $table->index('submitted_by');
                $table->index('confirmed_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_pengajuan_dana_realisasi');
        if (Schema::hasColumn('finance_pengajuan_dana_item', 'realisasi')) {
            Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
                $table->dropColumn('realisasi');
            });
        }
    }
};
