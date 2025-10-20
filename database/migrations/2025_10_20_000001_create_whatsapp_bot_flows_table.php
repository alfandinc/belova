<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('whatsapp_bot_flows', function (Blueprint $table) {
            $table->id();
            $table->string('session')->nullable()->index(); // null = global
            $table->string('flow_id')->index();
            $table->string('name')->nullable();
            $table->json('triggers')->nullable();
            $table->json('choices')->nullable();
            $table->text('fallback')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_bot_flows');
    }
};
