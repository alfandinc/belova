<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsAppConversation;
use App\Models\ERM\Visitation;

echo "Testing getVisitation method...\n";

// Get the active conversation
$conversation = WhatsAppConversation::where('phone_number', '6285172451701')
    ->where('conversation_state', 'pending')
    ->orderBy('created_at', 'desc')
    ->first();

if ($conversation) {
    echo "Found conversation ID: {$conversation->id}\n";
    echo "Type: {$conversation->conversation_type}\n";
    echo "Context data: " . json_encode($conversation->context_data) . "\n";
    
    // Check the conversation type
    echo "Expected type: " . WhatsAppConversation::TYPE_VISITATION_CONFIRMATION . "\n";
    echo "Actual type: {$conversation->conversation_type}\n";
    echo "Types match: " . ($conversation->conversation_type === WhatsAppConversation::TYPE_VISITATION_CONFIRMATION ? 'Yes' : 'No') . "\n";
    
    // Check context data
    $contextData = $conversation->context_data;
    echo "Has visitation_id: " . (isset($contextData['visitation_id']) ? 'Yes' : 'No') . "\n";
    
    if (isset($contextData['visitation_id'])) {
        $visitationId = $contextData['visitation_id'];
        echo "Visitation ID from context: $visitationId\n";
        
        // Try to find the visitation
        $visitation = Visitation::find($visitationId);
        echo "Visitation found: " . ($visitation ? 'Yes' : 'No') . "\n";
        
        // Test the getVisitation method
        echo "\nTesting getVisitation method:\n";
        $visitationFromMethod = $conversation->getVisitation();
        echo "getVisitation result: " . ($visitationFromMethod ? 'Found' : 'Not found') . "\n";
        
        if ($visitationFromMethod) {
            echo "Visitation ID: {$visitationFromMethod->id}\n";
        }
    }
} else {
    echo "No active conversation found!\n";
}