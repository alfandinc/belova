<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaMessage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WaMessageLogController extends Controller
{
    public function index()
    {
        return view('admin.wa_message_log');
    }

    public function data(Request $request)
    {
        // return one row per pasien+session: pick latest message (by id) for each combination
        // group by COALESCE(session_client_id, '') so NULL sessions are grouped consistently
        $ids = DB::table('wa_messages')
            ->selectRaw('MAX(id) as id')
            ->groupBy(DB::raw("COALESCE(session_client_id, '')"), 'pasien_id')
            ->pluck('id')
            ->toArray();

        $query = WaMessage::with('pasien')
            ->whereIn('id', $ids)
            ->select(['id','session_client_id','direction','from','to','body','message_id','pasien_id','created_at']);

        return DataTables::of($query)
            ->addColumn('session_display', function($row){
                // show session label (or client_id) and last known session number from outgoing messages
                $label = $row->session_client_id;
                try {
                    $s = \App\Models\WaSession::where('client_id', $row->session_client_id)->first();
                    if ($s && $s->label) $label = $s->label;
                } catch (\Exception $e) {}

                $phone = null;
                try {
                    $lastOut = DB::table('wa_messages')
                        ->where('session_client_id', $row->session_client_id)
                        ->where('direction', 'out')
                        ->orderByDesc('created_at')
                        ->first();
                    if ($lastOut) {
                        // prefer raw.meta.to_normalized or from_normalized if present in raw
                        $raw = null;
                        if (!empty($lastOut->raw)) {
                            try { $raw = json_decode($lastOut->raw, true); } catch (\Exception $e) { $raw = null; }
                        }
                        if (is_array($raw) && !empty($raw['meta']['from_normalized'])) {
                            $phone = $raw['meta']['from_normalized'];
                        } elseif (!empty($lastOut->from)) {
                            $phone = $lastOut->from;
                        }
                    }
                } catch (\Exception $e) { $phone = null; }

                $phoneText = '';
                if ($phone) {
                    $p = preg_replace('/\\D+/', '', $phone);
                    $phoneText = '<div class="text-muted" style="font-size:0.85em;">' . e($p) . '</div>';
                }
                return '<div>' . e($label) . '</div>' . $phoneText;
            })
            ->addColumn('pasien', function($row){
                if ($row->pasien) {
                    $url = route('admin.wa_messages.conversation', ['pasien' => $row->pasien->id]);
                    $name = e($row->pasien->nama);
                    $phone = e($row->pasien->no_hp ?: $row->pasien->no_hp2 ?: '');
                    $phoneHtml = $phone ? '<div class="text-muted" style="font-size:0.9em;">' . $phone . '</div>' : '';
                    return '<a href="' . $url . '" data-pasien-id="' . e($row->pasien->id) . '">' . $name . '</a>' . $phoneHtml;
                }
                return '<span class="text-muted">(no pasien)</span>';
            })
            ->addColumn('pasien_text', function($row){
                return $row->pasien ? $row->pasien->nama : '(no pasien)';
            })
            ->editColumn('body', function($row){ return Str::limit($row->body, 200); })
            ->rawColumns(['pasien','session_display'])
            ->orderColumn('created_at', '-created_at $1')
            ->make(true);
    }

    public function conversation(Request $request, $pasien)
    {
        $session = $request->query('session', null);
        if ($session === '') $session = null;

        $query = WaMessage::where('pasien_id', $pasien)->orderBy('created_at', 'asc');
        if (is_null($session)) {
            $query->whereNull('session_client_id');
        } else {
            $query->where('session_client_id', $session);
        }

        $messages = $query->get();

        // pasien name
        $pasienName = null;
        try { $p = \App\Models\ERM\Pasien::find($pasien); $pasienName = $p ? $p->nama : null; } catch (\Exception $e) { $pasienName = null; }

        // client label
        $clientLabel = null;
        if (!is_null($session)) {
            try { $s = \App\Models\WaSession::where('client_id', $session)->first(); $clientLabel = $s ? ($s->label ?: $s->client_id) : $session; } catch (\Exception $e) { $clientLabel = $session; }
        } else {
            $clientLabel = null;
        }

        return view('admin.wa_conversation', ['messages' => $messages, 'pasien_id' => $pasien, 'session' => $session, 'clientLabel' => $clientLabel, 'pasienName' => $pasienName]);
    }

    // Return partial HTML for modal chat (AJAX)
    public function conversationPartial(Request $request, $pasien)
    {
        $session = $request->query('session', null);
        if ($session === '') $session = null;

        $query = WaMessage::where('pasien_id', $pasien)->orderBy('created_at', 'asc');
        if (is_null($session)) {
            $query->whereNull('session_client_id');
        } else {
            $query->where('session_client_id', $session);
        }

        $messages = $query->get();

        // pasien name
        $pasienName = null;
        try { $p = \App\Models\ERM\Pasien::find($pasien); $pasienName = $p ? $p->nama : null; } catch (\Exception $e) { $pasienName = null; }

        // client label
        $clientLabel = null;
        if (!is_null($session)) {
            try { $s = \App\Models\WaSession::where('client_id', $session)->first(); $clientLabel = $s ? ($s->label ?: $s->client_id) : $session; } catch (\Exception $e) { $clientLabel = $session; }
        }

        return view('admin.partials.wa_conversation_partial', ['messages' => $messages, 'pasien_id' => $pasien, 'session' => $session, 'clientLabel' => $clientLabel, 'pasienName' => $pasienName]);
    }
}
