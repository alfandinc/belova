<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_lab_paket_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_paket_id')->constrained('erm_lab_paket')->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained('erm_lab_test')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lab_paket_id', 'lab_test_id'], 'erm_lab_paket_detail_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_lab_paket_detail');
    }
};