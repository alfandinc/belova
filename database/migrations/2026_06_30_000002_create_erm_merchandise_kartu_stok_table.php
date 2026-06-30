<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_merchandise_kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_id')->constrained('erm_merchandises')->onDelete('cascade');
            $table->dateTime('tanggal');
            $table->enum('type', ['in', 'out']);
            $table->integer('qty');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['merchandise_id', 'tanggal']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_merchandise_kartu_stok');
    }
};