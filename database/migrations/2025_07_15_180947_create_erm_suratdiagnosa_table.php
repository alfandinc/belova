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
        Schema::create('erm_suratdiagnosa', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_suratdiagnosa');
    }
};
