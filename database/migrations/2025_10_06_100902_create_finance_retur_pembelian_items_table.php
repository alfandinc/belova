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
        Schema::create('finance_retur_pembelian_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('finance_retur_pembelian')->onDelete('cascade');
            $table->foreignId('invoice_item_id')->constrained('finance_invoice_items')->onDelete('cascade');
            $table->string('name');
            $table->decimal('quantity_returned', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('billable_type')->nullable();
            $table->unsignedBigInteger('billable_id')->nullable();
            $table->timestamps();
            
            $table->index(['billable_type', 'billable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_retur_pembelian_items');
    }
};
