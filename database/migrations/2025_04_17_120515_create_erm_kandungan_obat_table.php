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
        Schema::create('erm_kandungan_obat', function (Blueprint $table) {
            $table->id();
            $table->string('obat_id'); // ubah jadi string
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->foreignId('zataktif_id')->constrained('erm_zataktif')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_kandungan_obat');
    }
};
