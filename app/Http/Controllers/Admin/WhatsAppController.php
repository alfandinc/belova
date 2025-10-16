<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class WhatsAppController extends Controller
{
    // WhatsApp integration removed - controller disabled

    public function index()
    {
        abort(404, 'WhatsApp integration removed');
    }

    public function getStatus()
    {
        return response()->json(['status' => 'disabled']);
    }

    public function testMessage()
    {
        return response()->json(['success' => false, 'error' => 'WhatsApp integration disabled']);
    }
}