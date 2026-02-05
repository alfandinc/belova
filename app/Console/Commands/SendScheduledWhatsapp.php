<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaScheduledMessage;
use App\Models\RunningWaScheduledMessage;
use App\Models\RunningWaMessageLog;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
            $this->info('No text/image scheduled messages to send.');
        }

        // process regular scheduled messages
        foreach ($rows as $row) {
            $payload = [ 'to' => $row->to ];
            // detect image payload encoded as JSON
            $decoded = null;
            if (!empty($row->message)) {
                $tmp = json_decode($row->message, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($tmp) && isset($tmp['type']) && $tmp['type'] === 'image' && !empty($tmp['image_url'])) {
                    $payload['image_url'] = $tmp['image_url'];
                    if (!empty($tmp['caption'])) $payload['caption'] = $tmp['caption'];
                } else {
                    $payload['message'] = $row->message;
                }
            } else {
                $payload['message'] = '';
            }
            if (!empty($row->client_id)) $payload['from'] = $row->client_id;

            try {
                $resp = Http::timeout(60)->post(config('app.wa_bot_url', 'http://localhost:3000') . '/send', $payload);
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

        // Now process running peserta scheduled messages
        $runningRows = RunningWaScheduledMessage::where('status', 'pending')
            ->where('schedule_at', '<=', $now)
            ->orderBy('schedule_at')
            ->limit($limit)
            ->get();

        if ($runningRows->isEmpty()) {
            $this->info('No running scheduled messages to send.');
            return 0;
        }

        foreach ($runningRows as $r) {
            // ensure an image exists; if not, attempt to generate using Browsershot
            if (empty($r->image_path)) {
                try {
                    if (class_exists('\Spatie\Browsershot\Browsershot')) {
                        $this->info("Generating ticket image for peserta {$r->peserta_id}");
                        $token = env('WA_BOT_TOKEN');
                        $base = rtrim(config('app.url', 'http://localhost'), '/');
                        $url = $base . '/running/ticket-html-public/' . urlencode($r->peserta_id);
                        if ($token) $url .= '?wa_bot_token=' . urlencode($token);
                        $dir = storage_path('app/public/running_tickets');
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $file = $dir . DIRECTORY_SEPARATOR . 'ticket-' . $r->peserta_id . '-' . time() . '.png';
                        try {
                            \Spatie\Browsershot\Browsershot::url($url)
                                ->windowSize(900,1200)
                                ->waitUntilNetworkIdle()
                                ->save($file);
                            $r->image_path = $file;
                            $r->save();
                        } catch (\Exception $e) {
                            $this->error('Browsershot failed: ' . $e->getMessage());
                        }
                    } else {
                        $this->warn('Browsershot not installed; skipping image generation. Run: composer require spatie/browsershot');
                    }
                } catch (\Exception $e) {
                    $this->error('Exception during image generation: ' . $e->getMessage());
                }
            }

            $payload = [ 'to' => $r->to, 'peserta_id' => $r->peserta_id ];
            if (!empty($r->client_id)) $payload['from'] = $r->client_id;
            if (!empty($r->image_path)) $payload['image_path'] = $r->image_path;

            try {
                $resp = Http::timeout(120)->post(config('app.wa_bot_url', 'http://localhost:3000') . '/send-ticket', $payload);
                $r->response = $resp->body();

                if ($resp->successful()) {
                    $r->status = 'sent';
                    $r->sent_at = Carbon::now();
                    $r->save();
                    $this->info("Sent running scheduled message #{$r->id} to {$r->to}");

                    // log to running_wa_message_logs
                    try {
                        $body = null;
                        $messageId = null;
                        $raw = null;
                        $respJson = null;
                        try { $respJson = json_decode($resp->body(), true); } catch (\Exception $ee) { $respJson = null; }
                        if (is_array($respJson)) {
                            $body = $respJson['body'] ?? null;
                            $messageId = $respJson['id'] ?? null;
                            $raw = json_encode($respJson);
                        } else {
                            $raw = $resp->body();
                        }
                        RunningWaMessageLog::create([
                            'peserta_id' => $r->peserta_id,
                            'scheduled_message_id' => $r->id,
                            'client_id' => $r->client_id,
                            'direction' => 'out',
                            'to' => $r->to,
                            'body' => $body,
                            'response' => $r->response,
                            'message_id' => $messageId,
                            'raw' => $raw
                        ]);
                    } catch (\Exception $e) {
                        $this->error('Failed to write running log: ' . $e->getMessage());
                    }
                } else {
                    $r->status = 'failed';
                    $r->save();
                    $this->error("Failed to send running #{$r->id}: " . substr($resp->body(),0,200));
                }
            } catch (\Exception $e) {
                $r->status = 'failed';
                $r->response = $e->getMessage();
                $r->save();
                $this->error("Exception for running #{$r->id}: " . $e->getMessage());
            }
        }

        return 0;
        foreach ($rows as $row) {
            $payload = [ 'to' => $row->to ];
            // detect image payload encoded as JSON
            $decoded = null;
            if (!empty($row->message)) {
                $tmp = json_decode($row->message, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($tmp) && isset($tmp['type']) && $tmp['type'] === 'image' && !empty($tmp['image_url'])) {
                    $payload['image_url'] = $tmp['image_url'];
                    if (!empty($tmp['caption'])) $payload['caption'] = $tmp['caption'];
                } else {
                    $payload['message'] = $row->message;
                }
            } else {
                $payload['message'] = '';
            }
            if (!empty($row->client_id)) $payload['from'] = $row->client_id;

            try {
                $resp = Http::timeout(60)->post(config('app.wa_bot_url', 'http://localhost:3000') . '/send', $payload);
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
