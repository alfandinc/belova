<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('erm_fakturretur_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fakturretur_id');
            $table->unsignedBigInteger('fakturbeli_item_id');
            $table->unsignedBigInteger('obat_id');
            $table->unsignedBigInteger('gudang_id');
            $table->integer('qty');
            $table->string('batch')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('alasan')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->foreign('fakturretur_id')->references('id')->on('erm_fakturretur')->onDelete('cascade');
            $table->foreign('fakturbeli_item_id')->references('id')->on('erm_fakturbeli_items')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreign('gudang_id')->references('id')->on('erm_gudang')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_fakturretur_items');
    }
};
