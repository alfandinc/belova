<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erm_tindakan_obat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tindakan_id');
            $table->unsignedBigInteger('obat_id');
            $table->timestamps();

            $table->foreign('tindakan_id')->references('id')->on('erm_tindakan')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');
            $table->unique(['tindakan_id', 'obat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_tindakan_obat');
    }
};
