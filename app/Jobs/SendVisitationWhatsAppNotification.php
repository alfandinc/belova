<?php

namespace App\Jobs;

use App\Models\ERM\Visitation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVisitationWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $visitationId;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    public function __construct($visitationId)
    {
        $this->visitationId = $visitationId;
    }

    public function handle()
    {
        $whatsappService = new WhatsAppService();
        
        // Check if WhatsApp service is enabled
        if (!config('whatsapp.enabled')) {
            Log::info('WhatsApp service disabled, skipping notification', [
                'visitation_id' => $this->visitationId
            ]);
            return;
        }
        
        // Check if WhatsApp service is connected
        if (!$whatsappService->isConnected()) {
            Log::warning('WhatsApp service not connected, skipping visitation notification', [
                'visitation_id' => $this->visitationId
            ]);
            return;
        }

        $result = $whatsappService->sendVisitationNotification($this->visitationId);
        
        if (!$result['success']) {
            Log::error('Failed to send WhatsApp visitation notification', [
                'visitation_id' => $this->visitationId,
                'error' => $result['error'],
                'attempt' => $this->attempts()
            ]);
            throw new \Exception($result['error']);
        }

        Log::info('WhatsApp visitation notification sent successfully', [
            'visitation_id' => $this->visitationId,
            'attempt' => $this->attempts()
        ]);
    }

    public function failed(\Exception $exception)
    {
        Log::error('WhatsApp visitation notification job failed permanently', [
            'visitation_id' => $this->visitationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    public function retryUntil()
    {
        return now()->addMinutes(30); // Stop retrying after 30 minutes
    }
}