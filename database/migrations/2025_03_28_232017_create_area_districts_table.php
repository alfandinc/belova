<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('regency_id')->constrained('area_regencies');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('area_districts');
    }
};
