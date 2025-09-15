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
        Schema::create('erm_mutasi_gudang_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mutasi_id');
            $table->unsignedBigInteger('obat_id');
            $table->integer('jumlah')->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index('mutasi_id');
            $table->index('obat_id');

            // Add foreign key constraints
            $table->foreign('mutasi_id')
                ->references('id')
                ->on('erm_mutasi_gudang')
                ->onDelete('cascade');

            $table->foreign('obat_id')
                ->references('id')
                ->on('erm_obat')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_mutasi_gudang_items');
    }
};
