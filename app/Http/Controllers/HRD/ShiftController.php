<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Store a newly created shift.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $shift = Shift::create($data);

        return response()->json([
            'success' => true,
            'shift'   => $shift,
        ]);
    }

    /**
     * Update an existing shift.
     */
    public function update(Request $request, Shift $shift)
    {
        $data = $this->validateData($request);
        $shift->update($data);

        return response()->json([
            'success' => true,
            'shift'   => $shift,
        ]);
    }

    /**
     * Remove the specified shift.
     * This will also cascade-delete any related schedules due to FK constraints.
     */
    public function destroy(Request $request, Shift $shift)
    {
        $shift->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Validate and normalize shift data.
     */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:191'],
            'start_time' => ['required', 'string', 'max:8'],
            'end_time'   => ['required', 'string', 'max:8'],
        ]);

        $validated['start_time'] = $this->normalizeTime($validated['start_time']);
        $validated['end_time']   = $this->normalizeTime($validated['end_time']);

        return $validated;
    }

    /**
     * Ensure time is in H:i:s format.
     */
    protected function normalizeTime(string $time): string
    {
        $time = trim($time);
        // Accept formats like HH:MM or HH:MM:SS; append ":00" if seconds omitted.
        if (strlen($time) === 5) {
            return $time . ':00';
        }

        return $time;
    }
}
