<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_master_faktur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obat_id');
            $table->unsignedBigInteger('pemasok_id');
            $table->decimal('harga', 20, 2);
            $table->integer('qty_per_box');
            $table->decimal('diskon', 20, 2)->default(0);
            $table->enum('diskon_type', ['percent', 'nominal'])->default('nominal');
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('id')->on('erm_pemasok')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_master_faktur');
    }
};
