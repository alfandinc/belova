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
        Schema::create('erm_alergis', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('katakunci')->nullable();
            $table->json('kandungan_obat')->nullable();
            $table->json('makanan')->nullable();
            $table->string('verifikasi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_alergis');
    }
};
