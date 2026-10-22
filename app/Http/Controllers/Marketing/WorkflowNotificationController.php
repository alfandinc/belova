<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Support\Marketing\WorkflowNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Polled by the Content Plan / Request Desain pages for new workflow events of the user. */
class WorkflowNotificationController extends Controller
{
    public function feed(Request $request)
    {
        return response()->json(WorkflowNotifications::feed(Auth::user(), (int) $request->query('since', 0)));
    }
}
