<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketingDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole('Marketing')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('marketing.dashboard');
    }
}
