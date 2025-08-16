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
        Schema::create('erm_screening_batuk', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->enum('batuk_2minggu', ['ya', 'tidak']);
            $table->enum('demam', ['ya', 'tidak']);
            $table->enum('sesak_napas', ['ya', 'tidak']);
            $table->enum('pilek', ['ya', 'tidak']);
            $table->enum('sakit_tenggorokan', ['ya', 'tidak']);
            $table->enum('kontak_covid', ['ya', 'tidak']);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_screening_batuk');
    }
};
