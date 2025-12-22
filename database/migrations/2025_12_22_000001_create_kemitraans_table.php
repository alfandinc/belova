<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workdoc_kemitraans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('partner_name');
            $table->string('category')->nullable();
            $table->string('perihal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workdoc_kemitraans');
    }
};
