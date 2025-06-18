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
        Schema::create('erm_birthday_greetings', function (Blueprint $table) {
             $table->id();
            $table->string('pasien_id');
            $table->date('greeting_date');
            $table->integer('greeting_year');
            $table->unsignedBigInteger('greeting_by')->nullable();
            $table->text('greeting_message')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('cascade');
            $table->foreign('greeting_by')->references('id')->on('users')->onDelete('set null');
            
            // Ensure we only have one greeting per patient per year
            $table->unique(['pasien_id', 'greeting_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_birthday_greetings');
    }
};
