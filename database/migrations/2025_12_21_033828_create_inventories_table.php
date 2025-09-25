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
        Schema::create('bcl_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('inv_number');
            $table->string('name');
            $table->string('notes')->nullable();
            $table->string('images')->nullable();
            $table->integer('maintanance_period')->nullable();
            $table->enum('maintanance_cycle', ['Minggu', 'Bulan', 'Tahun'])->nullable();
            $table->enum('type', ['Private/Room', 'Public']);
            $table->integer('assigned_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_inventories');
    }
};
