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
        Schema::create('erm_alergi', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id', 6)->nullable();
            $table->string('status')->nullable();
            $table->string('katakunci')->nullable();
            $table->foreignId('zataktif_id')->constrained('erm_zataktif')->onDelete('cascade');
            $table->boolean('verif_status')->default(0);
            $table->foreignId('varifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_alergi');
    }
};
