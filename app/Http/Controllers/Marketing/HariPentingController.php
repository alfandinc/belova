<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\HariPenting;

class HariPentingController extends Controller
{
    public function index()
    {
        return view('marketing.hari_penting.index');
    }

    public function events(Request $request)
    {
        $events = HariPenting::query()->orderBy('start_date')->get()->map(function($row){
            return [
                'id' => $row->id,
                'title' => $row->title,
                'start' => $row->start_date->toDateString(),
                'end' => $row->end_date ? $row->end_date->copy()->addDay()->toDateString() : null, // FullCalendar exclusive end
                'allDay' => $row->all_day,
                'color' => $row->color ?? '#4e73df',
                'extendedProps' => [
                    'description' => $row->description,
                    'range' => $row->end_date ? $row->start_date->format('d M Y'). ' - ' . $row->end_date->format('d M Y') : $row->start_date->format('d M Y')
                ]
            ];
        });
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'all_day' => 'nullable|boolean'
        ]);
        $data['all_day'] = $request->boolean('all_day', true);
        $hari = HariPenting::create($data);
        return response()->json(['success' => true, 'data' => $hari]);
    }

    public function destroy($id)
    {
        $hari = HariPenting::findOrFail($id);
        $hari->delete();
        return response()->json(['success' => true]);
    }
}
