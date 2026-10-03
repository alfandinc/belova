<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Total of APPROVED retur pembelian for the invoice (refunded to the patient).
     * The invoice items / totals stay as sold; this column records how much was returned.
     */
    public function up(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_invoices', 'retur_amount')) {
                $table->decimal('retur_amount', 15, 2)->default(0)->after('shortage_amount');
            }
        });

        // Backfill from returs that are already approved.
        DB::statement("
            UPDATE finance_invoices i
            INNER JOIN (
                SELECT invoice_id, SUM(total_amount) AS total
                FROM finance_retur_pembelian
                WHERE status = 'approved' AND deleted_at IS NULL
                GROUP BY invoice_id
            ) r ON r.invoice_id = i.id
            SET i.retur_amount = r.total
        ");
    }

    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropColumn('retur_amount');
        });
    }
};
