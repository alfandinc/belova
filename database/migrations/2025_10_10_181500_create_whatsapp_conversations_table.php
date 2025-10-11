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
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number'); // Patient's phone number
            $table->string('conversation_type'); // Type: 'visitation_confirmation', 'appointment_reminder', etc.
            $table->string('conversation_state')->default('pending'); // State: 'pending', 'confirmed', 'cancelled', 'expired'
            $table->json('context_data')->nullable(); // Store related data (visitation_id, patient_id, etc.)
            $table->timestamp('expires_at')->nullable(); // When this conversation expires
            $table->timestamps();
            
            $table->index(['phone_number', 'conversation_state'], 'wa_conv_phone_state_idx');
            $table->index(['conversation_type', 'conversation_state'], 'wa_conv_type_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};