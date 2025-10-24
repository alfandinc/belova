<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finance_pengajuan_dana_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pengajuan_id');
            $table->string('nama_item');
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('harga_total', 15, 2)->virtualAs('jumlah * harga_satuan')->nullable();
            $table->timestamps();

            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('finance_pengajuan_dana')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_pengajuan_dana_item');
    }
};
