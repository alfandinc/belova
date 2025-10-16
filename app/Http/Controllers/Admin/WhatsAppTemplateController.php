<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class WhatsAppTemplateController extends Controller
{
    // WhatsApp template management disabled

    public function index()
    {
        abort(404);
    }

    public function update()
    {
        return response()->json(['success' => false, 'error' => 'WhatsApp integration disabled']);
    }

    public function preview()
    {
        return response()->json(['success' => false, 'error' => 'WhatsApp integration disabled']);
    }
}