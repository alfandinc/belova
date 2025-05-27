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
        Schema::create('erm_resepdokter', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('visitation_id');
            $table->foreignId('obat_id')->constrained('erm_obat')->onDelete('cascade');
            $table->integer('jumlah')->nullable();
            $table->string('dosis')->nullable();
            $table->integer('bungkus')->nullable();
            $table->integer('racikan_ke')->nullable();
            $table->string('aturan_pakai')->nullable();
            $table->foreignId('wadah_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_resepdokter');
    }
};
