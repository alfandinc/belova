<?php

namespace App\Services;

// WhatsApp integration disabled - stub service to keep codebase stable.
class WhatsAppChatbotService
{
    public function __construct(...$args)
    {
        // no-op
    }

    public function processIncomingMessage($phoneNumber, $messageText)
    {
        // Integration removed; return a neutral response
        return ['status' => 'disabled', 'message' => 'WhatsApp integration is disabled'];
    }
}