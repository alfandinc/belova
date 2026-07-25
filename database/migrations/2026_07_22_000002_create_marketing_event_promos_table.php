<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingEventPromosTable extends Migration
{
    public function up()
    {
        Schema::create('marketing_event_promos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('promo_id');
            $table->timestamps();

            $table->unique(['event_id', 'promo_id']);
            $table->foreign('event_id')->references('id')->on('marketing_event')->onDelete('cascade');
            $table->foreign('promo_id')->references('id')->on('marketing_promos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketing_event_promos');
    }
}