<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHppToFinanceInvoiceItemsTable extends Migration
{
    public function up()
    {
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->decimal('hpp', 12, 2)->nullable()->after('unit_price');
        });
    }

    public function down()
    {
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
}
