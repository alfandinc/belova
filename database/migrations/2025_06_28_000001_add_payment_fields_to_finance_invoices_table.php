<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToFinanceInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total_amount');
            $table->decimal('change_amount', 12, 2)->default(0)->after('amount_paid');
            $table->enum('payment_method', ['cash', 'non_cash', 'mixed'])->default('cash')->after('change_amount');
        });
    }

    public function down()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'change_amount', 'payment_method']);
        });
    }
}
