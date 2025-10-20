<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsAppBotFlow;
use App\Models\WhatsAppScheduledMessage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class WhatsAppAdminApiController extends Controller
{
    // Flows
    public function listFlows(Request $req)
    {
        $session = $req->query('session');
        $q = WhatsAppBotFlow::query();
        if ($session) $q->where('session', $session);
        else $q->whereNull('session');
        $flows = $q->get();
        return response()->json(['flows' => $flows]);
    }

    public function saveFlow(Request $req)
    {
        $data = $req->validate([
            'session' => 'nullable|string',
            'flow_id' => 'required|string',
            'name' => 'nullable|string',
            'triggers' => 'nullable|array',
            'choices' => 'nullable|array',
            'fallback' => 'nullable|string',
        ]);
        $flow = WhatsAppBotFlow::updateOrCreate(
            ['session' => $data['session'] ?? null, 'flow_id' => $data['flow_id']],
            ['name' => $data['name'] ?? null, 'triggers' => $data['triggers'] ?? [], 'choices' => $data['choices'] ?? [], 'fallback' => $data['fallback'] ?? null]
        );
        return response()->json(['success' => true, 'flow' => $flow]);
    }

    public function deleteFlow($id)
    {
        $f = WhatsAppBotFlow::find($id);
        if (!$f) return response()->json(['success' => false], 404);
        $f->delete();
        return response()->json(['success' => true]);
    }

    // Scheduled messages
    public function listScheduled(Request $req)
    {
        $session = $req->query('session');
        $q = WhatsAppScheduledMessage::query();
        if ($session) $q->where('session', $session);
        $jobs = $q->orderBy('send_at', 'asc')->get();
        return response()->json(['scheduled' => $jobs]);
    }

    // Public endpoints for internal sync (Node -> Laravel)
    // Protected with a shared token via ?token= or X-WHATSAPP-TOKEN header
    protected function validateSyncToken(Request $req)
    {
        $token = $req->query('token') ?? $req->header('X-WHATSAPP-TOKEN');
        $expected = env('WHATSAPP_SYNC_TOKEN', '');
        return $token && $expected && hash_equals($expected, $token);
    }

    public function listFlowsPublic(Request $req)
    {
        if (!$this->validateSyncToken($req)) return response()->json(['error' => 'forbidden'], 403);
        return $this->listFlows($req);
    }

    public function listScheduledPublic(Request $req)
    {
        if (!$this->validateSyncToken($req)) return response()->json(['error' => 'forbidden'], 403);
        return $this->listScheduled($req);
    }

    // Proxy sessions from the Node whatsapp service so the admin UI can fetch them
    public function listSessions()
    {
        $node = env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000');
        try {
            $resp = Http::timeout(5)->get(rtrim($node, '/') . '/sessions');
            if ($resp->successful()) {
                // forward as-is
                return response()->json($resp->json());
            }
            return response()->json(['sessions' => []], $resp->status());
        } catch (\Exception $e) {
            return response()->json(['sessions' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function createScheduled(Request $req)
    {
        $data = $req->validate([
            'session' => 'nullable|string',
            'number' => 'required|string',
            'message' => 'nullable|string',
            'sendAt' => 'required|date',
            'maxAttempts' => 'nullable|integer',
        ]);
        $job = WhatsAppScheduledMessage::create([
            'session' => $data['session'] ?? 'belova',
            'number' => $data['number'],
            'message' => $data['message'] ?? '',
            'send_at' => $data['sendAt'],
            'max_attempts' => $data['maxAttempts'] ?? 3,
        ]);
        return response()->json(['success' => true, 'job' => $job]);
    }

    public function deleteScheduled($id)
    {
        $j = WhatsAppScheduledMessage::find($id);
        if (!$j) return response()->json(['success' => false], 404);
        $j->delete();
        return response()->json(['success' => true]);
    }

    // Node polling will call these endpoints to mark scheduled jobs as sent/failed
    public function markScheduledSent(Request $req, $id)
    {
        if (!$this->validateSyncToken($req)) return response()->json(['error' => 'forbidden'], 403);
        $j = WhatsAppScheduledMessage::find($id);
        if (!$j) return response()->json(['error' => 'not_found'], 404);
        $j->sent = true;
        $j->failed = false;
        $j->sent_at = now();
        $j->attempts = $j->attempts ?? 0;
        $j->save();
        return response()->json(['success' => true, 'job' => $j]);
    }

    public function markScheduledFailed(Request $req, $id)
    {
        if (!$this->validateSyncToken($req)) return response()->json(['error' => 'forbidden'], 403);
        $data = $req->validate([ 'last_error' => 'nullable|string', 'attempts' => 'nullable|integer', 'failed' => 'nullable|boolean' ]);
        $j = WhatsAppScheduledMessage::find($id);
        if (!$j) return response()->json(['error' => 'not_found'], 404);
        if (isset($data['attempts'])) $j->attempts = $data['attempts'];
        $j->last_error = $data['last_error'] ?? $j->last_error;
        if (isset($data['failed'])) $j->failed = $data['failed'];
        if ($j->failed) $j->failed_at = now();
        $j->save();
        return response()->json(['success' => true, 'job' => $j]);
    }
}
