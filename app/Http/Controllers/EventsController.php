<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventsController extends Controller
{
    public function index()
    {
        // Static list of events for now; Belova Mengaji is one of them
        $events = [
            [
                'title' => 'Belova Mengaji',
                'url' => url('/belova-mengaji'),
                'description' => 'Penilaian & Absensi Belova Mengaji'
            ],
            // add more events here in future
        ];

        return view('events.dashboard', compact('events'));
    }
}
