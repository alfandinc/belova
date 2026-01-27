<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingPromoItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_promo_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('promo_id');
            $table->string('item_type'); // 'tindakan' or 'obat'
            $table->unsignedBigInteger('item_id');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->index('promo_id');
            $table->foreign('promo_id')->references('id')->on('marketing_promos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_promo_items');
    }
}
