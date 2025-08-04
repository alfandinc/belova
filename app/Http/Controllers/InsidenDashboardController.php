<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InsidenDashboardController extends Controller
{
    public function index()
    {
        return view('insiden.dashboard');
    }
}
