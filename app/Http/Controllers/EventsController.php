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
            [
                'title' => 'Lebaran',
                'url' => route('events.lebaran.index'),
                'description' => 'Data pasien untuk event Lebaran'
            ],
            // add more events here in future
        ];

        return view('events.dashboard', compact('events'));
    }
}
