<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AkreditasiDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('akreditasi.dashboard');
    }
}
