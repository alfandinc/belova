<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('hrd_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Shift name (pagi, middle, siang, malam)
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrd_shifts');
    }
};
