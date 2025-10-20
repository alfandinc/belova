<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('whatsapp_scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session')->index();
            $table->string('number');
            $table->text('message')->nullable();
            $table->dateTimeTz('send_at');
            $table->integer('max_attempts')->default(3);
            $table->integer('attempts')->default(0);
            $table->boolean('sent')->default(false);
            $table->boolean('failed')->default(false);
            $table->text('last_error')->nullable();
            $table->dateTimeTz('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_scheduled_messages');
    }
};
