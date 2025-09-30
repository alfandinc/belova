<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToFinanceBilling extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (Schema::hasTable('finance_billing')) {
			Schema::table('finance_billing', function (Blueprint $table) {
				if (!Schema::hasColumn('finance_billing', 'deleted_at')) {
					$table->softDeletes();
				}
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasTable('finance_billing')) {
			Schema::table('finance_billing', function (Blueprint $table) {
				if (Schema::hasColumn('finance_billing', 'deleted_at')) {
					$table->dropSoftDeletes();
				}
			});
		}
	}
}

