<?php

namespace App\Services;

// WhatsAppService stub: integration removed. This file intentionally returns disabled responses.
class WhatsAppService
{
    public function __construct()
    {
        // no-op
    }

    public function isConnected()
    {
        return false;
    }

    public function getServiceHealth()
    {
        return ['status' => 'disabled'];
    }

    public function sendMessage($number, $message)
    {
        return ['success' => false, 'error' => 'WhatsApp integration disabled'];
    }

    public function sendVisitationNotification($visitationId)
    {
        return ['success' => false, 'error' => 'WhatsApp integration disabled'];
    }
}