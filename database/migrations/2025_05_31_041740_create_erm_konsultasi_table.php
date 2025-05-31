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
        Schema::create('erm_konsultasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // nama tindakan atau konsultasi
            $table->decimal('harga', 15, 2); // tarif jasa

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_konsultasi');
    }
};
