<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRunningWaMessageLogsTable extends Migration
{
    public function up()
    {
        Schema::create('running_wa_message_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('peserta_id')->nullable();
            $table->unsignedBigInteger('scheduled_message_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('direction')->nullable();
            $table->string('to')->nullable();
            $table->text('body')->nullable();
            $table->text('response')->nullable();
            $table->string('message_id')->nullable();
            $table->longText('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('running_wa_message_logs');
    }
}
