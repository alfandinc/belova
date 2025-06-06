<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceInvoiceItemsTable extends Migration
{
    public function up()
    {
        Schema::create('finance_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->enum('discount_type', ['%', 'nominal'])->nullable();
            $table->decimal('final_amount', 12, 2);
            $table->string('billable_type')->nullable();
            $table->unsignedBigInteger('billable_id')->nullable();
            $table->timestamps();

            $table->index(['billable_type', 'billable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('finance_invoice_items');
    }
}
