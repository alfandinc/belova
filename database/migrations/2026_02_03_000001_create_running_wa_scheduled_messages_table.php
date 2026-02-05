<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRunningWaScheduledMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('running_wa_scheduled_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('peserta_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('to');
            $table->text('message')->nullable();
            $table->timestamp('schedule_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('running_wa_scheduled_messages');
    }
}
