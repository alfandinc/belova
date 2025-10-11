<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsAppConversation;

echo "Checking conversations for phone 6285172451701:\n";
$conversations = WhatsAppConversation::where('phone_number', '6285172451701')->get();

if ($conversations->count() > 0) {
    foreach ($conversations as $conv) {
        echo "ID: {$conv->id}, Type: {$conv->conversation_type}, State: {$conv->conversation_state}\n";
        echo "Context: " . json_encode($conv->context_data) . "\n";
        echo "Expires: {$conv->expires_at}\n";
        echo "---\n";
    }
} else {
    echo "No conversations found for this phone number.\n";
}

echo "\nAll conversations in database:\n";
$all = WhatsAppConversation::all();
foreach ($all as $conv) {
    echo "Phone: {$conv->phone_number}, Type: {$conv->conversation_type}\n";
    echo "Context: " . json_encode($conv->context_data) . "\n";
    echo "---\n";
}