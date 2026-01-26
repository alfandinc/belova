<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wa_scheduled_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id')->nullable();
            $table->string('to');
            $table->text('message');
            $table->timestamp('schedule_at');
            $table->string('status')->default('pending');
            $table->text('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_scheduled_messages');
    }
};
