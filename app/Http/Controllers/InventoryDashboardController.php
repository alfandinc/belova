<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole('Inventaris')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('Inventory.dashboard');
    }
}
