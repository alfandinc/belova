<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaScheduledMessage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SendScheduledWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wa:send-scheduled {--limit=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled WhatsApp messages that are due';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $now = Carbon::now();

        $rows = WaScheduledMessage::where('status', 'pending')
            ->where('schedule_at', '<=', $now)
            ->orderBy('schedule_at')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No scheduled messages to send.');
            return 0;
        }

        foreach ($rows as $row) {
            $payload = [
                'to' => $row->to,
                'message' => $row->message,
            ];
            if (!empty($row->client_id)) $payload['from'] = $row->client_id;

            try {
                $resp = Http::post(config('app.wa_bot_url', 'http://localhost:3000') . '/send', $payload);
                $row->response = $resp->body();

                if ($resp->successful()) {
                    $row->status = 'sent';
                    $row->sent_at = Carbon::now();
                    $row->save();
                    $this->info("Sent scheduled message #{$row->id} to {$row->to}");
                } else {
                    $row->status = 'failed';
                    $row->save();
                    $this->error("Failed to send #{$row->id}: " . substr($resp->body(),0,200));
                }
            } catch (\Exception $e) {
                $row->status = 'failed';
                $row->response = $e->getMessage();
                $row->save();
                $this->error("Exception for #{$row->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
