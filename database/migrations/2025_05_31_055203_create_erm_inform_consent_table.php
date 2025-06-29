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
        Schema::create('erm_inform_consent', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->foreignId('tindakan_id')->constrained('erm_tindakan')->cascadeOnDelete();
            $table->unsignedBigInteger('paket_id')->nullable();
            $table->foreign('paket_id')->references('id')->on('erm_paket_tindakan')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('before_image_path')->nullable();
            $table->string('after_image_path')->nullable();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_inform_consent');
    }
};
