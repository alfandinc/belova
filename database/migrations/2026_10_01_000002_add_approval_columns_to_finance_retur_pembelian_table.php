<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retur pembelian approval: created as "pending" (no stock / cash effect yet),
     * then approved (stock back + refund transaction) or rejected by Admin/Finance.
     */
    public function up(): void
    {
        Schema::table('finance_retur_pembelian', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_retur_pembelian', 'status')) {
                $table->string('status', 20)->default('pending')->after('processed_date')->index();
            }

            if (!Schema::hasColumn('finance_retur_pembelian', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('finance_retur_pembelian', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('finance_retur_pembelian', 'rejected_reason')) {
                $table->string('rejected_reason', 500)->nullable()->after('approved_at');
            }
        });

        // Existing returs were already processed (stock + transaction applied).
        DB::table('finance_retur_pembelian')->update([
            'status' => 'approved',
            'approved_by' => DB::raw('user_id'),
            'approved_at' => DB::raw('COALESCE(processed_date, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('finance_retur_pembelian', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'approved_by', 'approved_at', 'rejected_reason']);
        });
    }
};
