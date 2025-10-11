<?php

use Illuminate\Support\Facades\Route;
use App\Services\WhatsAppService;

Route::get('/test-whatsapp', function () {
    $whatsappService = new WhatsAppService();
    
    // Test connection
    $isConnected = $whatsappService->isConnected();
    $health = $whatsappService->getServiceHealth();
    
    if (!$isConnected) {
        return response()->json([
            'success' => false,
            'message' => 'WhatsApp service not connected. Please scan QR code first.',
            'health' => $health
        ]);
    }
    
    // Test sending a message (replace with your phone number)
    $testNumber = '6281234567890'; // Replace with your actual number
    $result = $whatsappService->sendTestMessage($testNumber);
    
    return response()->json([
        'success' => $result['success'],
        'message' => $result['success'] ? 'Test message sent successfully!' : $result['error'],
        'health' => $health,
        'connected' => $isConnected
    ]);
});