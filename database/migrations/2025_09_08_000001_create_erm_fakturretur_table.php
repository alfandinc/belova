<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('erm_fakturretur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fakturbeli_id');
            $table->unsignedBigInteger('pemasok_id')->nullable();
            $table->string('no_retur')->unique();
            $table->date('tanggal_retur');
            $table->text('notes')->nullable();
                $table->string('status')->default('diajukan');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('fakturbeli_id')->references('id')->on('erm_fakturbeli')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('id')->on('erm_pemasok')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_fakturretur');
    }
};
