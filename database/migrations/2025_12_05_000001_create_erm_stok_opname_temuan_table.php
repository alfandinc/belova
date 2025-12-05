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
        Schema::create('erm_stok_opname_temuan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stok_opname_id');
            $table->unsignedBigInteger('stok_opname_item_id');
            $table->decimal('qty', 14, 4)->default(0);
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Optional indexes for lookups
            $table->index('stok_opname_id');
            $table->index('stok_opname_item_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_stok_opname_temuan');
    }
};
