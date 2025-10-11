<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $baseUrl;
    private $timeout;
    private $retryAttempts;
    private $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.service_url', 'http://localhost:3000');
        $this->timeout = config('whatsapp.timeout', 30);
        $this->retryAttempts = config('whatsapp.retry_attempts', 3);
        $this->retryDelay = config('whatsapp.retry_delay', 5);
    }

    public function isConnected()
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/status');
            return $response->successful() && $response->json('connected', false);
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Connection Check Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getServiceHealth()
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/health');
            if ($response->successful()) {
                return $response->json();
            }
            return ['status' => 'error', 'message' => 'Service not responding'];
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Health Check Failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function sendMessage($number, $message)
    {
        try {
            $cleanNumber = $this->cleanPhoneNumber($number);
            
            $response = Http::timeout($this->timeout)->post($this->baseUrl . '/send-message', [
                'number' => $cleanNumber,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'number' => $cleanNumber,
                    'message_length' => strlen($message)
                ]);
                return $response->json();
            }

            Log::error('WhatsApp API Error', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return ['success' => false, 'error' => 'Failed to send message: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

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

    public function sendVisitationNotification($visitationId)
    {
        try {
            $visitation = \App\Models\ERM\Visitation::with(['pasien', 'dokter.user', 'klinik'])
                ->find($visitationId);
            
            if (!$visitation) {
                return ['success' => false, 'error' => 'Visitation not found'];
            }

            if (!$visitation->pasien->no_hp) {
                return ['success' => false, 'error' => 'Patient has no phone number'];
            }

            // Use the chatbot service to send interactive message
            $chatbotService = app(\App\Services\WhatsAppChatbotService::class);
            return $chatbotService->sendInteractiveVisitationMessage($visitation);
            
        } catch (\Exception $e) {
            Log::error('Error sending visitation notification', [
                'visitation_id' => $visitationId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function buildVisitationMessage($visitation)
    {
        $jenisKunjungan = '';
        switch ($visitation->jenis_kunjungan) {
            case 1: $jenisKunjungan = 'Konsultasi Dokter'; break;
            case 2: $jenisKunjungan = 'Pembelian Produk'; break;
            case 3: $jenisKunjungan = 'Laboratorium'; break;
            default: $jenisKunjungan = 'Kunjungan'; break;
        }

        // Get template from database
        $template = \App\Models\WhatsAppTemplate::getByKey('visitation');
        
        if (!$template) {
            // Fallback to default message if template not found
            return "🏥 *KONFIRMASI KUNJUNGAN*\n\n" .
                   "Halo {pasien_nama},\n\n" .
                   "Kunjungan Anda telah terdaftar!\n" .
                   "Tanggal: {tanggal_visitation}\n" .
                   "Waktu: {waktu_kunjungan}\n" .
                   "Dokter: {dokter_nama}\n\n" .
                   "Terima kasih!";
        }
        
        // Prepare replacement values
        $replacements = [
            'pasien_nama' => $visitation->pasien->nama ?? '',
            'pasien_id' => $visitation->pasien->id ?? '',
            'jenis_kunjungan' => $jenisKunjungan,
            'tanggal_visitation' => $visitation->tanggal_visitation ? date('d/m/Y', strtotime($visitation->tanggal_visitation)) : '',
            'waktu_kunjungan' => $visitation->waktu_kunjungan ? date('H:i', strtotime($visitation->waktu_kunjungan)) : 'Tidak ditentukan',
            'no_antrian' => $visitation->no_antrian ?? 'Tidak ada',
            'dokter_nama' => $visitation->dokter && $visitation->dokter->user ? 'Dr. ' . $visitation->dokter->user->name : 'Tidak ditentukan',
            'klinik_nama' => $visitation->klinik ? $visitation->klinik->nama_klinik : 'Tidak ditentukan'
        ];

        // Use the template's processContent method
        return $template->processContent($replacements);
    }

    public function sendTestMessage($phoneNumber, $testMessage = null)
    {
        $message = $testMessage ?? "🏥 *BELOVA CLINIC TEST*\n\nHalo! Ini adalah pesan test dari sistem Belova Clinic.\n\nSistem WhatsApp berfungsi dengan baik! ✅\n\n_Pesan test otomatis_";
        
        return $this->sendMessage($phoneNumber, $message);
    }
}