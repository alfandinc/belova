<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsAppConversation;

echo "Testing findByVisitationId method...\n\n";

// Test with known visitation ID
$visitationId = '202510101859146296370';
echo "Looking for conversation with visitation ID: $visitationId\n";

$conversation = WhatsAppConversation::findByVisitationId($visitationId);

if ($conversation) {
    echo "✅ Found conversation: {$conversation->id}\n";
    echo "State: {$conversation->conversation_state}\n";
    
    $visitation = $conversation->getVisitation();
    echo "Visitation exists: " . ($visitation ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ No conversation found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Testing new conversation finding logic...\n\n";

$phoneNumber = '6285172451701';

// Simulate the new logic
echo "Step 1: Finding active conversation...\n";
$conversation = WhatsAppConversation::findActiveConversation($phoneNumber);
if ($conversation) {
    echo "Found active conversation: {$conversation->id}\n";
    $visitation = $conversation->getVisitation();
    echo "Has valid visitation: " . ($visitation ? 'Yes' : 'No') . "\n";
} else {
    echo "No active conversation found\n";
}

echo "\nStep 2: Finding recent conversations with valid visitation...\n";
$recentConversations = WhatsAppConversation::where('phone_number', $phoneNumber)
    ->where('conversation_type', WhatsAppConversation::TYPE_VISITATION_CONFIRMATION)
    ->where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->get();

$validConversation = null;
foreach ($recentConversations as $conv) {
    echo "Checking conversation {$conv->id}... ";
    if ($conv->getVisitation()) {
        echo "✅ Has valid visitation\n";
        $validConversation = $conv;
        break;
    } else {
        echo "❌ No valid visitation\n";
    }
}

if ($validConversation) {
    echo "\n✅ Final result: Using conversation {$validConversation->id}\n";
} else {
    echo "\n❌ No conversation with valid visitation found\n";
}