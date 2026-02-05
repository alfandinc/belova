<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use App\Models\ERM\Pasien;
use App\Models\WaScheduledMessage;
use Carbon\Carbon;

class WhatsappTestController extends Controller
{
    public function index()
    {
        // Load sessions from DB and preserve `client_id` as string
        $rows = \Illuminate\Support\Facades\DB::table('wa_sessions')
            ->select('client_id as id', 'label')
            ->get()
            ->toArray();

        $sessions = array_map(function($s){
            return ['id' => $s->id, 'status' => 'unknown', 'label' => $s->label ?? null];
        }, $rows);

        return view('admin.whatsapp_test', ['sessions' => $sessions]);
    }

    public function send(Request $request)
    {
        // support bulk: when `to[]` is submitted as an array
        $isBulk = is_array($request->input('to'));

        // optional global schedule field from `datetime-local` input
        $scheduleAt = $request->input('schedule_at');

        if ($isBulk) {
            $tos = $request->input('to', []);
            $messages = $request->input('message', []);
            $pasienIds = $request->input('pasien_id', []);
            $images = $request->file('image') ?: [];

            $dt = null;
            if (!empty($scheduleAt)) {
                try { $dt = Carbon::createFromFormat('Y-m-d\TH:i', $scheduleAt); } catch (\Exception $e) { $dt = null; }
            }

            $count = 0;
            foreach ($tos as $i => $to) {
                $to = trim((string)$to);
                if ($to === '') continue;

                $messageText = isset($messages[$i]) ? $messages[$i] : '';
                $storedMessage = $messageText;

                // handle image at same index
                if (isset($images[$i]) && $images[$i]) {
                    try {
                        $file = $images[$i];
                        if (is_array($file) && isset($file['tmp_name'])) {
                            // in some PHP setups files may come as array-like; skip
                        }
                        $path = Storage::disk('public')->putFile('wa_uploads', $file);
                        $url = rtrim($request->root(), '/') . '/storage/' . ltrim($path, '/');
                        $storedMessage = json_encode([ 'type' => 'image', 'image_url' => $url, 'caption' => $messageText ]);
                    } catch (\Exception $e) {
                        // skip this row on failure to store
                        continue;
                    }
                }

                WaScheduledMessage::create([
                    'client_id' => $request->input('from') ?: null,
                    'to' => $to,
                    'message' => $storedMessage,
                    'schedule_at' => $dt ?: Carbon::now(),
                    'status' => 'pending',
                ]);
                $count++;
            }

            return back()->with('success', "Queued {$count} messages for background sending");
        }

        // single-send path
        $data = $request->validate([
            'to' => 'required|string',
            'message' => 'nullable|string',
            'image' => 'nullable|file|image|max:5120'
        ]);

        if (!empty($scheduleAt)) {
            try {
                $dt = Carbon::createFromFormat('Y-m-d\TH:i', $scheduleAt);
            } catch (\Exception $e) {
                return back()->with('error', 'Invalid schedule datetime format');
            }

            // prepare stored message; if image uploaded, store and encode metadata
            $storedMessage = $data['message'] ?? '';
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    $path = Storage::disk('public')->putFile('wa_uploads', $file);
                    // build URL using current request root so subdirectory installs work (e.g. /belova/public)
                    $url = rtrim($request->root(), '/') . '/storage/' . ltrim($path, '/');
                    $storedMessage = json_encode([ 'type' => 'image', 'image_url' => $url, 'caption' => $data['message'] ?? '' ]);
                } catch (\Exception $e) {
                    return back()->with('error', 'Failed to store uploaded image: ' . $e->getMessage());
                }
            }

            WaScheduledMessage::create([
                'client_id' => $request->input('from') ?: null,
                'to' => $data['to'],
                'message' => $storedMessage,
                'schedule_at' => $dt,
                'status' => 'pending',
            ]);

            return back()->with('success', 'Message scheduled successfully');
        }


            // immediate send: if image uploaded, queue as scheduled message for background sending
            if ($request->hasFile('image')) {
                try {
                    $file = $request->file('image');
                    $path = Storage::disk('public')->putFile('wa_uploads', $file);
                    $url = rtrim($request->root(), '/') . '/storage/' . ltrim($path, '/');

                    $storedMessage = json_encode([ 'type' => 'image', 'image_url' => $url, 'caption' => $data['message'] ?? '' ]);

                    WaScheduledMessage::create([
                        'client_id' => $request->input('from') ?: null,
                        'to' => $data['to'],
                        'message' => $storedMessage,
                        'schedule_at' => Carbon::now(),
                        'status' => 'pending',
                    ]);

                    return back()->with('success', 'Image uploaded and queued for sending (background).');
                } catch (\Exception $e) {
                    return back()->with('error', 'Failed to store/upload image: ' . $e->getMessage());
                }
            }

            // text-only immediate send
            $payload = [ 'to' => $data['to'], 'message' => $data['message'] ?? '' ];
            if ($request->filled('from')) $payload['from'] = $request->input('from');

            try {
                $resp = Http::timeout(60)->post(config('app.wa_bot_url', 'http://localhost:3000') . '/send', $payload);

                if ($resp->successful()) {
                    return back()->with('success', 'Message queued/sent successfully');
                }

                return back()->with('error', 'Bot responded: ' . $resp->body());
            } catch (\Exception $e) {
                return back()->with('error', 'Request failed: ' . $e->getMessage());
            }
    }

    /**
     * AJAX Select2 pasien search
     */
    public function pasienSearch(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        $query = Pasien::query();
        if (!empty($q)) {
            $query->where(function($w) use ($q) {
                $w->where('nama', 'like', "%$q%")
                  ->orWhere('no_hp', 'like', "%$q%")
                  ->orWhere('no_hp2', 'like', "%$q%");
            });
        }

        $rows = $query->limit(25)->get(['id', 'nama', 'no_hp', 'no_hp2']);

        $results = $rows->map(function($p){
            $phone = $p->no_hp ?: $p->no_hp2 ?: '';
            return [
                'id' => $p->id,
                'text' => ($p->nama ?: '-') . ($phone ? " – $phone" : ''),
                'phone' => $phone,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }
}
