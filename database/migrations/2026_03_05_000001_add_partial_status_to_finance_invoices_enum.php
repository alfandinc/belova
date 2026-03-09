<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Extend enum to support partial payments (amount_paid > 0 but < total_amount)
        DB::statement("ALTER TABLE finance_invoices MODIFY status ENUM('draft','issued','partial','paid','canceled') NOT NULL DEFAULT 'draft'");

        // Backfill existing rows (keep draft/canceled untouched)
        DB::table('finance_invoices')
            ->where('status', 'issued')
            ->where('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '>=', 'total_amount')
            ->update(['status' => 'paid']);

        DB::table('finance_invoices')
            ->where('status', 'issued')
            ->where('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '<', 'total_amount')
            ->update(['status' => 'partial']);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Downgrade safely: map existing 'partial' rows back to 'issued' before shrinking enum.
        DB::table('finance_invoices')->where('status', 'partial')->update(['status' => 'issued']);

        DB::statement("ALTER TABLE finance_invoices MODIFY status ENUM('draft','issued','paid','canceled') NOT NULL DEFAULT 'draft'");
    }
};
