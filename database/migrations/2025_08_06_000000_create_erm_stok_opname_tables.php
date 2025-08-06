<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erm_stok_opname', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_opname');
            $table->unsignedBigInteger('gudang_id');
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedSmallInteger('periode_tahun');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('gudang_id')->references('id')->on('erm_gudang')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('erm_stok_opname_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stok_opname_id');
            $table->unsignedBigInteger('obat_id');
            $table->integer('stok_sistem');
            $table->integer('stok_fisik');
            $table->integer('selisih');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stok_opname_id')->references('id')->on('erm_stok_opname')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_stok_opname_items');
        Schema::dropIfExists('erm_stok_opname');
    }
};
