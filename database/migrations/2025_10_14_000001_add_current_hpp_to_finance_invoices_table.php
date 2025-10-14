<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentHppToFinanceInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->decimal('current_hpp', 12, 2)->nullable()->after('payment_method');
        });
    }

    public function down()
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropColumn('current_hpp');
        });
    }
}
