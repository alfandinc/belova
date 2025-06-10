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
        Schema::create('hrd_position', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            // e.g. "Manager", "Staff"

            $table->foreignId('division_id')->constrained('hrd_division')->onDelete('cascade');
            // e.g. "HR Manager", "Staff IT"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrd_position');
    }
};
