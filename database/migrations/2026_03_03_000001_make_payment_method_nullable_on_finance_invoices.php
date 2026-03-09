<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Allow payment_method to be blank for issued/unpaid invoices.
        // Use VARCHAR to avoid enum constraint drift (app supports many methods).
        DB::statement("ALTER TABLE finance_invoices MODIFY payment_method VARCHAR(50) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        // Backfill NULLs before restoring NOT NULL constraint.
        DB::statement("UPDATE finance_invoices SET payment_method = 'cash' WHERE payment_method IS NULL");
        DB::statement("ALTER TABLE finance_invoices MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'");
    }
};
