<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class WhatsAppWebhookController extends Controller
{
    // Integration removed - endpoint disabled

    public function handleIncomingMessage()
    {
        return response()->json(['status' => 'disabled']);
    }

    public function verifyWebhook()
    {
        return response('Forbidden', 403);
    }
}