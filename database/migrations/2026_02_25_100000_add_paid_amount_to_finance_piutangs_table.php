<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidAmountToFinancePiutangsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('finance_piutangs', function (Blueprint $table) {
            // track how much has been paid so far
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_piutangs', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
}
