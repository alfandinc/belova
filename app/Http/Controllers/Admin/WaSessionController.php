<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaSession;

class WaSessionController extends Controller
{
    public function index()
    {
        // Use query builder to avoid Eloquent casting the `id` attribute
        // (selecting `client_id as id` on an Eloquent model may be treated
        // as the primary key and get cast to integer => 0).
        $rows = \Illuminate\Support\Facades\DB::table('wa_sessions')
            ->select('client_id as id', 'label')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|string|unique:wa_sessions,client_id',
            'label' => 'nullable|string'
        ]);

        $ws = WaSession::create([
            'client_id' => $data['client_id'],
            'label' => $data['label'] ?? null
        ]);

        return back()->with('success', 'Session added: ' . $ws->client_id);
    }

    public function destroy(WaSession $waSession)
    {
        $waSession->delete();
        return back()->with('success', 'Session removed');
    }
}
