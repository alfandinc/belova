<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\WhatsAppChatbotService;
use App\Services\WhatsAppService;
use App\Models\WhatsAppConversation;

echo "Testing chatbot service with existing conversation...\n";

// Create the services
$whatsappService = new WhatsAppService();
$chatbotService = new WhatsAppChatbotService($whatsappService);

// Test with the phone number that has existing conversations
$phoneNumber = '6285172451701';
$message = '2'; // Patient wants to cancel

echo "Processing message from: $phoneNumber\n";
echo "Message: $message\n\n";

// First, check what conversations exist for this phone
echo "Existing conversations for this phone:\n";
$conversations = WhatsAppConversation::where('phone_number', $phoneNumber)->get();
foreach ($conversations as $conv) {
    echo "ID: {$conv->id}, State: {$conv->conversation_state}, Created: {$conv->created_at}\n";
    echo "Context: " . json_encode($conv->context_data) . "\n\n";
}

// Process the message
$result = $chatbotService->processIncomingMessage($phoneNumber, $message);

echo "Processing result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";