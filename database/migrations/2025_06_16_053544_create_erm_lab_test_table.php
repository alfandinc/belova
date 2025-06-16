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
        Schema::create('erm_lab_test', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('lab_kategori_id');
            $table->decimal('harga', 12, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->foreign('lab_kategori_id')->references('id')->on('erm_lab_kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_lab_test');
    }
};
