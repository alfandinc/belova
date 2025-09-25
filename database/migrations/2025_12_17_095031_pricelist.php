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
        Schema::create('bcl_pricelist', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 10, 2);
            $table->integer('jangka_waktu');
            $table->string('jangka_sewa');
            $table->integer('bonus_waktu');
            $table->string('bonus_sewa')->nullable();
            $table->integer('room_category');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_pricelist');
    }
};
