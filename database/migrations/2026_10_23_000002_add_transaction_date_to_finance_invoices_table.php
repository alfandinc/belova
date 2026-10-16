<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the invoice was transacted (payment first processed: paid, piutang or asuransi; stock cut).
     * Omset is dated by this. payment_date stays "fully paid at".
     *
     * Backfill for already processed invoices: stock cut date, else first finance transaction,
     * else payment_date, else created_at.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('finance_invoices', 'transaction_date')) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->dateTime('transaction_date')->nullable()->after('payment_date')->index();
            });
        }

        $stockCut = Schema::hasTable('erm_kartu_stok')
            ? "(SELECT MIN(ks.tanggal) FROM erm_kartu_stok ks WHERE ks.ref_type = 'invoice_penjualan' AND ks.ref_id = fi.id)"
            : 'NULL';
        $firstTransaction = Schema::hasTable('finance_transactions')
            ? '(SELECT MIN(ft.tanggal) FROM finance_transactions ft WHERE ft.invoice_id = fi.id)'
            : 'NULL';

        DB::statement("
            UPDATE finance_invoices fi
            SET fi.transaction_date = COALESCE({$stockCut}, {$firstTransaction}, fi.payment_date, fi.created_at)
            WHERE fi.transaction_date IS NULL
              AND fi.payment_method IS NOT NULL
              AND fi.payment_method <> ''
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('finance_invoices', 'transaction_date')) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->dropIndex(['transaction_date']);
                $table->dropColumn('transaction_date');
            });
        }
    }
};
