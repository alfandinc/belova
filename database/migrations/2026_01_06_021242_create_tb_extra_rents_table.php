<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bcl_extra_rent', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('parent_trans');
            $table->string('nama');
            $table->integer('qty');
            $table->integer('lama_sewa');
            $table->string('jangka_sewa');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->decimal('harga', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_extra_rent');
    }
};
