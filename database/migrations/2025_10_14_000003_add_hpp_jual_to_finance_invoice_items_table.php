<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHppJualToFinanceInvoiceItemsTable extends Migration
{
    public function up()
    {
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->decimal('hpp_jual', 12, 2)->nullable()->after('hpp');
        });
    }

    public function down()
    {
        Schema::table('finance_invoice_items', function (Blueprint $table) {
            $table->dropColumn('hpp_jual');
        });
    }
}
