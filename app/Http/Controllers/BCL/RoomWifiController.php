<?php

namespace App\Http\Controllers\BCL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BCL\RoomWifi;
use App\Models\BCL\Rooms;
use Yajra\DataTables\DataTables;

class RoomWifiController extends Controller
{
    public function index()
    {
        $rooms = Rooms::orderBy('room_name')->get();
        return view('bcl.room_wifi.index', compact('rooms'));
    }

    public function data(Request $request)
    {
        $query = RoomWifi::with('room')->select('bcl_room_wifi.*');
        return DataTables::of($query)
            ->addColumn('room_name', function ($row) {
                return $row->room ? $row->room->room_name : '-';
            })
            ->addColumn('actions', function ($row) {
                $edit = "<button class='btn btn-sm btn-primary btn-edit' data-id='{$row->id}'>Edit</button>";
                $del = " <button class='btn btn-sm btn-danger btn-delete' data-id='{$row->id}'>Delete</button>";
                return $edit . $del;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    protected function normalizeActive(Request $request)
    {
        // Normalize checkbox values to boolean for validation
        if ($request->has('active')) {
            $request->merge(['active' => true]);
        } else {
            $request->merge(['active' => false]);
        }
    }

    public function store(Request $request)
    {
        $this->normalizeActive($request);

        $data = $request->validate([
            'room_id' => 'required|exists:bcl_rooms,id',
            'ssid' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'active' => 'required|boolean',
        ]);

        $wifi = RoomWifi::create($data);

        return response()->json(['status' => 'success', 'data' => $wifi]);
    }

    public function edit($id)
    {
        $wifi = RoomWifi::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $wifi]);
    }

    public function update(Request $request, $id)
    {
        $this->normalizeActive($request);

        $wifi = RoomWifi::findOrFail($id);
        $data = $request->validate([
            'room_id' => 'required|exists:bcl_rooms,id',
            'ssid' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'active' => 'required|boolean',
        ]);

        $wifi->update($data);
        return response()->json(['status' => 'success', 'data' => $wifi]);
    }

    public function destroy($id)
    {
        $wifi = RoomWifi::findOrFail($id);
        $wifi->delete();
        return response()->json(['status' => 'success']);
    }
}
