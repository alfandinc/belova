<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_client_id')->nullable();
            $table->string('direction')->nullable(); // 'in' or 'out'
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('body')->nullable();
            $table->string('message_id')->nullable();
            $table->text('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_messages');
    }
};
