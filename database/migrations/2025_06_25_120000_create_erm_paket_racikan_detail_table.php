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
        Schema::create('erm_paket_racikan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_racikan_id')->constrained('erm_paket_racikan')->onDelete('cascade');
            $table->foreignId('obat_id')->constrained('erm_obat')->onDelete('cascade');
            $table->string('dosis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_paket_racikan_detail');
    }
};
