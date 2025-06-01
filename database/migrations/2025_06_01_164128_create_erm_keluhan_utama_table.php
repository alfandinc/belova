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
        Schema::create('erm_keluhan_utama', function (Blueprint $table) {
            $table->id();
            $table->string('keluhan'); // Keluhan utama name
            $table->unsignedBigInteger('spesialisasi_id'); // Foreign key for specialization
            $table->foreign('spesialisasi_id')->references('id')->on('erm_spesialisasis')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_keluhan_utama');
    }
};
