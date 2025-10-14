<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCurrentHppFromFinanceInvoices extends Migration
{
    public function up()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('finance_invoices', 'current_hpp')) {
                $table->dropColumn('current_hpp');
            }
        });
    }

    public function down()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->decimal('current_hpp', 12, 2)->nullable()->after('payment_method');
        });
    }
}
