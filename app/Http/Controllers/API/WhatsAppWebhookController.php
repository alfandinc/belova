<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected $chatbotService;

    public function __construct(WhatsAppChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming WhatsApp messages from Baileys webhook
     */
    public function handleIncomingMessage(Request $request)
    {
        try {
            Log::info('WhatsApp webhook received', $request->all());

            $messageData = $request->all();
            
            // Extract message information
            $phoneNumber = $this->extractPhoneNumber($messageData);
            $messageText = $this->extractMessageText($messageData);
            
            if (!$phoneNumber || !$messageText) {
                Log::warning('Invalid webhook data', [
                    'phone' => $phoneNumber,
                    'text' => $messageText,
                    'data' => $messageData
                ]);
                return response()->json(['status' => 'ignored']);
            }

            // Clean phone number (remove country code if needed)
            $cleanPhone = $this->cleanPhoneNumber($phoneNumber);
            
            // Process the message through chatbot service
            $response = $this->chatbotService->processIncomingMessage($cleanPhone, $messageText);
            
            Log::info('Chatbot response', [
                'phone' => $cleanPhone,
                'message' => $messageText,
                'response' => $response
            ]);

            return response()->json([
                'status' => 'processed',
                'response' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract phone number from webhook data
     */
    private function extractPhoneNumber($data)
    {
        // Adjust this based on your Baileys webhook structure
        return $data['from'] ?? $data['phone'] ?? $data['sender'] ?? null;
    }

    /**
     * Extract message text from webhook data
     */
    private function extractMessageText($data)
    {
        // Adjust this based on your Baileys webhook structure
        return $data['message'] ?? $data['text'] ?? $data['body'] ?? null;
    }

    /**
     * Clean phone number format
     */
    private function cleanPhoneNumber($number)
    {
        // Remove all non-digit characters
        $clean = preg_replace('/[^0-9]/', '', $number);
        
        // Add country code if not present (assuming Indonesia +62)
        if (strlen($clean) > 0) {
            if (substr($clean, 0, 1) === '0') {
                $clean = '62' . substr($clean, 1);
            } elseif (substr($clean, 0, 2) !== '62') {
                $clean = '62' . $clean;
            }
        }
        
        return $clean;
    }

    /**
     * Webhook verification (if needed)
     */
    public function verifyWebhook(Request $request)
    {
        // Add webhook verification logic if your WhatsApp service requires it
        $verifyToken = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');
        
        if ($verifyToken === config('whatsapp.verify_token', 'belova_webhook_token')) {
            return response($challenge);
        }
        
        return response('Forbidden', 403);
    }
}