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
        Schema::create('erm_asesmen_perawats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitation_id')->constrained('erm_visitations')->onDelete('cascade');
            $table->text('keluhan_utama')->nullable();
            $table->text('alergi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_asesmen_perawats');
    }
};
