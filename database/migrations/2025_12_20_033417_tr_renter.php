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
        Schema::create('bcl_tr_renter', function (Blueprint $table) {
            $table->id();
            $table->string('trans_id');
            $table->string('identity');
            $table->integer('id_renter');
            $table->date('tanggal');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->integer('room_id');
            $table->integer('lama_sewa');
            $table->string('jangka_sewa');
            $table->decimal('harga', 10, 2);
            $table->integer('free_sewa')->nullable();
            $table->string('free_jangka')->nullable();
            $table->string('catatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_tr_renter');
    }
};
