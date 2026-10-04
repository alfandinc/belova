<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan dana payments: every transfer for a pengajuan (a pengajuan may be paid in several parts,
 * or closed with a smaller amount than requested). payment_status becomes unpaid | partial | paid.
 *
 * Safe to run on any system: the table is only created when missing, and the backfill only adds
 * a payment for paid pengajuan that do not have one yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_pengajuan_dana_payment')) {
            Schema::create('finance_pengajuan_dana_payment', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pengajuan_id');
                $table->decimal('nominal', 15, 2);
                $table->dateTime('tanggal_bayar');
                $table->string('bukti')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('paid_by')->nullable(); // users.id (no FK: user id types differ between systems)
                $table->timestamps();

                $table->foreign('pengajuan_id')->references('id')->on('finance_pengajuan_dana')->cascadeOnDelete();
                $table->index('tanggal_bayar');
                $table->index('paid_by');
            });
        }

        // pengajuan paid before this feature were always paid in full: record that as one payment
        if (Schema::hasColumn('finance_pengajuan_dana', 'payment_status')) {
            $paidDate = Schema::hasColumn('finance_pengajuan_dana', 'payment_date')
                ? 'COALESCE(d.payment_date, d.updated_at, NOW())'
                : 'COALESCE(d.updated_at, NOW())';
            DB::statement("INSERT INTO finance_pengajuan_dana_payment (pengajuan_id, nominal, tanggal_bayar, created_at, updated_at)
                SELECT d.id, d.grand_total, {$paidDate}, NOW(), NOW()
                FROM finance_pengajuan_dana d
                WHERE d.payment_status = 'paid'
                  AND NOT EXISTS (SELECT 1 FROM finance_pengajuan_dana_payment p WHERE p.pengajuan_id = d.id)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_pengajuan_dana_payment');
        if (Schema::hasColumn('finance_pengajuan_dana', 'payment_status')) {
            DB::table('finance_pengajuan_dana')->where('payment_status', 'partial')->update(['payment_status' => 'unpaid']);
        }
    }
};
