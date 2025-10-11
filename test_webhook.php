<?php

// Test script to simulate webhook call
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Services\WhatsAppChatbotService;
use App\Services\WhatsAppService;
use App\Models\WhatsAppConversation;

// Create test conversation
$conversation = WhatsAppConversation::create([
    'phone_number' => '628123456789', // Replace with actual phone number from screenshot
    'conversation_type' => 'visitation_confirmation',
    'conversation_state' => 'pending',
    'context_data' => [
        'visitation_id' => 1,
        'patient_id' => 1
    ],
    'expires_at' => now()->addHours(24)
]);

echo "Test conversation created with ID: " . $conversation->id . "\n";

// Test webhook processing
$chatbotService = new WhatsAppChatbotService(new WhatsAppService());
$result = $chatbotService->processIncomingMessage('628123456789', '1');

echo "Chatbot response: " . json_encode($result) . "\n";

echo "Conversation state after processing: " . $conversation->fresh()->conversation_state . "\n";