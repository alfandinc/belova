<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkdocDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // Display the dashboard
        return view('workdoc.dashboard');
    }
}
