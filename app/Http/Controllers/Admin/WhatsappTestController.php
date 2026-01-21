<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use App\Models\ERM\Pasien;

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
        $data = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string'
        ]);

        $payload = [
            'to' => $data['to'],
            'message' => $data['message']
        ];
        if ($request->filled('from')) $payload['from'] = $request->input('from');

        try {
            $resp = Http::post(config('app.wa_bot_url', 'http://localhost:3000') . '/send', $payload);

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
