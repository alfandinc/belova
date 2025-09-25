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
        Schema::create('bcl_room_category_image', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('room_category_id')->nullable();
            $table->string('image');
            $table->enum('tag', ['room', 'public'])->default('room');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_room_category_image');
    }
};
