<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('invoice_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'issued', 'paid', 'canceled'])->default('draft');
            $table->timestamp('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('discount_type', ['%', 'nominal'])->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('finance_invoices');
    }
}
