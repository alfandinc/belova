<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsAppConversation;

echo "Testing conversation finding logic...\n";

$phoneNumber = '6285172451701';

echo "1. Finding active conversation (pending only):\n";
$activeConv = WhatsAppConversation::findActiveConversation($phoneNumber);
if ($activeConv) {
    echo "Found ID: {$activeConv->id}, State: {$activeConv->conversation_state}, Created: {$activeConv->created_at}\n";
    echo "Context: " . json_encode($activeConv->context_data) . "\n\n";
} else {
    echo "No active conversation found\n\n";
}

echo "2. Finding most recent conversation (within 24 hours):\n";
$recentConv = WhatsAppConversation::where('phone_number', $phoneNumber)
    ->where('conversation_type', WhatsAppConversation::TYPE_VISITATION_CONFIRMATION)
    ->where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->first();

if ($recentConv) {
    echo "Found ID: {$recentConv->id}, State: {$recentConv->conversation_state}, Created: {$recentConv->created_at}\n";
    echo "Context: " . json_encode($recentConv->context_data) . "\n\n";
} else {
    echo "No recent conversation found\n\n";
}

echo "3. All conversations for this phone:\n";
$allConvs = WhatsAppConversation::where('phone_number', $phoneNumber)
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($allConvs as $conv) {
    $isActive = $conv->conversation_state === 'pending' && (!$conv->expires_at || $conv->expires_at->isFuture());
    echo "ID: {$conv->id}, State: {$conv->conversation_state}, Created: {$conv->created_at}, Active: " . ($isActive ? 'Yes' : 'No') . "\n";
    echo "Context: " . json_encode($conv->context_data) . "\n";
    echo "Expires: {$conv->expires_at}\n\n";
}