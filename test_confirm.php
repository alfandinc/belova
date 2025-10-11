<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\WhatsAppChatbotService;
use App\Services\WhatsAppService;

echo "Testing chatbot service with confirmation (option 1)...\n";

// Create the services
$whatsappService = new WhatsAppService();
$chatbotService = new WhatsAppChatbotService($whatsappService);

// Test with confirmation
$phoneNumber = '6285172451701';
$message = '1'; // Patient wants to confirm

echo "Processing message from: $phoneNumber\n";
echo "Message: $message\n\n";

// Process the message
$result = $chatbotService->processIncomingMessage($phoneNumber, $message);

echo "Processing result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";