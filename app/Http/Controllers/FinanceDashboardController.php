<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Kasir','Admin'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('finance.dashboard');
    }
}
