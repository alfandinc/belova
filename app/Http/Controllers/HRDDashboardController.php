<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HRDDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('hrd.dashboard');
    }
}
