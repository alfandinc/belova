<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ERMDashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['Dokter', 'Perawat', 'Pendaftaran'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return view('erm.dashboard');
    }

    public function daftarpasien()
    {
        return view('erm.daftarpasien');
    }
}
