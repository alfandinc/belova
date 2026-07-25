<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDiscountTypeAndValueToMarketingPromoItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_promo_items', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('percent')->after('item_id');
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
        });

        DB::table('marketing_promo_items')
            ->update([
                'discount_type' => 'percent',
                'discount_value' => DB::raw('COALESCE(discount_percent, 0)'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_promo_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
    }
}