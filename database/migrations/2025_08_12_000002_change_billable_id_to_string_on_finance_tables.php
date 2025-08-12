<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change billable_id in finance_billing to string
        Schema::table('finance_billing', function (Blueprint $table) {
            $table->string('billable_id')->nullable()->change();
        });
        // Change billable_id in finance_invoice_items to string
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->string('billable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert billable_id in finance_billing to integer
        Schema::table('finance_billing', function (Blueprint $table) {
            $table->integer('billable_id')->nullable()->change();
        });
        // Revert billable_id in finance_invoice_items to integer
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->integer('billable_id')->nullable()->change();
        });
    }
};
