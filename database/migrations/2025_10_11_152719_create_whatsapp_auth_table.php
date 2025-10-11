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
        Schema::create('whatsapp_auth', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->unique(); // e.g., 'creds', 'pre-key-1', 'sender-key-xxx'
            $table->json('data'); // The actual auth data
            $table->timestamps();
            
            $table->index('key_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_auth');
    }
};
