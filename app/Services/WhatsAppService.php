<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService - HTTP client wrapper to a wwebjs-based microservice.
 *
 * Configure the target service in your .env:
 * WHATSAPP_SERVICE_URL=http://localhost:3000
 */
class WhatsAppService
{
    protected $baseUrl;

    public function __construct()
    {
    $this->baseUrl = env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000');
    }

    public function isConnected()
    {
        try {
            $res = Http::timeout(3)->get($this->baseUrl . '/status');
            if ($res->successful()) {
                $json = $res->json();
                return isset($json['status']) && $json['status'] === 'ready';
            }
        } catch (\Exception $e) {
            Log::debug('WhatsAppService isConnected check failed: ' . $e->getMessage());
        }
        return false;
    }

    // overloaded to accept a session id (optional)
    public function isConnectedSession($session = null)
    {
        try {
            $url = $this->baseUrl . '/status' . ($session ? ('?session=' . urlencode($session)) : '');
            $res = Http::timeout(3)->get($url);
            if ($res->successful()) {
                $json = $res->json();
                return isset($json['status']) && $json['status'] === 'ready';
            }
        } catch (\Exception $e) {
            Log::debug('WhatsAppService isConnectedSession check failed: ' . $e->getMessage());
        }
        return false;
    }

    public function getServiceHealth()
    {
        try {
            $res = Http::timeout(3)->get($this->baseUrl . '/status');
            return $res->successful() ? $res->json() : ['status' => 'unreachable', 'http_code' => $res->status()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a message through the whatsapp-service.
     * @param string $number E.164-ish digits (country code + number)
     * @param string $message
     * @param string|null $session Optional session/clientId to use (default: belova)
     * @return array
     */
    public function sendMessage($number, $message, $session = null)
    {
        // normalize number to digits only (caller should include country code)
        $clean = preg_replace('/[^0-9]/', '', (string) $number);
        if (empty($clean)) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }

        try {
            $body = ['number' => $clean, 'message' => $message ?? ''];
            if ($session) $body['session'] = $session;

            $res = Http::timeout(10)->post($this->baseUrl . '/send', $body);

            if ($res->successful()) {
                return ['success' => true, 'response' => $res->json()];
            }

            return ['success' => false, 'error' => 'Service returned HTTP ' . $res->status(), 'body' => $res->body()];
        } catch (\Exception $e) {
            Log::error('WhatsAppService sendMessage error: ' . $e->getMessage(), ['number' => $number]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send message with one or more attachments by forwarding multipart/form-data to the Node service.
     * @param string $number
     * @param string $message
     * @param array $files Array of UploadedFile instances
     * @param string|null $session
     * @return array
     */
    public function sendMessageWithAttachments($number, $message, $files = [], $session = null)
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $number);
        if (empty($clean)) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }

        try {
            // Try simpler approach: move uploaded files into whatsapp-service tmp_uploads folder
            // and send JSON with absolute file paths to avoid multipart parsing issues.
            $tmpDir = base_path('whatsapp-service/tmp_uploads');
            if (!file_exists($tmpDir)) @mkdir($tmpDir, 0755, true);

            $paths = [];
            foreach ($files as $f) {
                if (!is_object($f)) continue;
                $name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $f->getClientOriginalName());
                $dest = $tmpDir . DIRECTORY_SEPARATOR . $name;
                // move the uploaded file to the tmp dir
                $moved = $f->move($tmpDir, $name);
                if ($moved) {
                    $paths[] = $dest;
                }
            }

            $body = ['number' => $clean, 'message' => $message ?? ''];
            if ($session) $body['session'] = $session;
            if (count($paths)) $body['file_paths'] = $paths;

            // send as JSON; Node will read files from the provided paths
            $res = Http::timeout(120)->post($this->baseUrl . '/send', $body);

            if ($res->successful()) {
                return ['success' => true, 'response' => $res->json()];
            }

            return ['success' => false, 'error' => 'Service returned HTTP ' . $res->status(), 'body' => $res->body()];
        } catch (\Exception $e) {
            Log::error('WhatsAppService sendMessageWithAttachments error: ' . $e->getMessage(), ['number' => $number]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendVisitationNotification($visitationId)
    {
        // Keep existing API for callers; implement domain-specific message composition here.
        return ['success' => false, 'error' => 'Not implemented: sendVisitationNotification'];
    }
}