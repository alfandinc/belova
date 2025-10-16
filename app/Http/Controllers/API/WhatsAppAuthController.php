<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class WhatsAppAuthController extends Controller
{
	// Placeholder - integration removed

	public function authenticate()
	{
		return response()->json(['success' => false, 'error' => 'WhatsApp integration disabled']);
	}
}
