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
        Schema::create('finance_revenue_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinik_id')->constrained('erm_klinik')->cascadeOnDelete();
            $table->decimal('target_amount', 15, 2);
            $table->unsignedTinyInteger('periode_bulan');
            $table->integer('periode_tahun');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['klinik_id', 'periode_bulan', 'periode_tahun'], 'finance_revenue_target_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_revenue_target');
    }
};
