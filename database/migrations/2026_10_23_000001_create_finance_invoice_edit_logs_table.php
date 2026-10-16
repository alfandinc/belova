<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin edits of processed invoices (item price / discount, invoice discount / tax): who, when, why,
     * and the values before and after.
     */
    public function up(): void
    {
        if (!Schema::hasTable('finance_invoice_edit_logs')) {
            Schema::create('finance_invoice_edit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->json('old_values');
                $table->json('new_values');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_invoice_edit_logs');
    }
};
