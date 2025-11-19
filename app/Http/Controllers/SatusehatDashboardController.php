<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SatusehatDashboardController extends Controller
{
    public function index()
    {
        // Basic data can be passed here later
        return view('satusehat.index');
    }
}
