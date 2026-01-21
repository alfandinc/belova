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
        // return one row per pasien: pick latest message (by id) for each pasien_id (including NULL grouped together)
        $ids = DB::table('wa_messages')->selectRaw('MAX(id) as id')->groupBy('pasien_id')->pluck('id')->toArray();

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

    public function conversation($pasien)
    {
        $messages = WaMessage::where('pasien_id', $pasien)->orderBy('created_at', 'asc')->get();
        return view('admin.wa_conversation', ['messages' => $messages, 'pasien_id' => $pasien]);
    }

    // Return partial HTML for modal chat (AJAX)
    public function conversationPartial($pasien)
    {
        $messages = WaMessage::where('pasien_id', $pasien)->orderBy('created_at', 'asc')->get();
        return view('admin.partials.wa_conversation_partial', ['messages' => $messages, 'pasien_id' => $pasien]);
    }
}
