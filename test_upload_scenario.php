<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate the exact upload scenario for Sofia
echo "=== Simulating Sofia's Upload Data ===\n";

// Based on the original screenshot, Sofia's attendance data would look like:
$sofiaData = [
    '13' => [ // finger_id
        '2025-08-13' => [
            '2025-08-13 15:58:44',
            '2025-08-13 16:00:13'
        ],
        '2025-08-14' => [
            '2025-08-14 00:00:30',
            '2025-08-14 08:59:00',
            '2025-08-14 17:44:00',
            '2025-08-14 17:29:00'
        ]
    ]
];

// Get Sofia's employee record
$sofia = App\Models\Employee::where('finger_id', '13')->first();
if (!$sofia) {
    echo "Sofia not found!\n";
    exit;
}

echo "Sofia found: {$sofia->nama} (ID: {$sofia->id})\n\n";

// Get her schedules
$schedule13 = App\Models\EmployeeSchedule::where('employee_id', $sofia->id)
    ->where('date', '2025-08-13')
    ->with('shift')
    ->first();

$schedule14 = App\Models\EmployeeSchedule::where('employee_id', $sofia->id)
    ->where('date', '2025-08-14')
    ->with('shift')
    ->first();

echo "Aug 13 Schedule: " . ($schedule13 ? $schedule13->shift->start_time . '-' . $schedule13->shift->end_time : 'None') . "\n";
echo "Aug 14 Schedule: " . ($schedule14 ? $schedule14->shift->start_time . '-' . $schedule14->shift->end_time : 'None') . "\n\n";

// Test the controller logic with reflection
$controller = new App\Http\Controllers\HRD\AbsensiRekapController();
$reflection = new ReflectionClass($controller);

// Test isOvernightShift method
$isOvernightMethod = $reflection->getMethod('isOvernightShift');
$isOvernightMethod->setAccessible(true);

if ($schedule13 && $schedule13->shift) {
    $isOvernight13 = $isOvernightMethod->invoke($controller, $schedule13->shift->start_time, $schedule13->shift->end_time);
    echo "Aug 13 is overnight shift: " . ($isOvernight13 ? 'YES' : 'NO') . "\n";
}

if ($schedule14 && $schedule14->shift) {
    $isOvernight14 = $isOvernightMethod->invoke($controller, $schedule14->shift->start_time, $schedule14->shift->end_time);
    echo "Aug 14 is overnight shift: " . ($isOvernight14 ? 'YES' : 'NO') . "\n";
}

echo "\n=== Testing Time Selection ===\n";

// Test Aug 13 (overnight shift) with cross-date times
if ($schedule13 && $schedule13->shift && $isOvernight13) {
    $aug13Times = array_merge($sofiaData['13']['2025-08-13'], $sofiaData['13']['2025-08-14']);
    echo "Aug 13 available times (merged): " . implode(', ', $aug13Times) . "\n";
    
    $findMethod = $reflection->getMethod('findBestAttendanceTimes');
    $findMethod->setAccessible(true);
    
    [$masuk13, $keluar13] = $findMethod->invoke($controller, $aug13Times, $schedule13->shift->start_time, $schedule13->shift->end_time, '2025-08-13');
    
    echo "Aug 13 Selected: Masuk={$masuk13}, Keluar={$keluar13}\n";
    echo "Aug 13 Expected: Masuk=2025-08-13 15:58:44, Keluar=2025-08-14 00:00:30\n";
}

// Test Aug 14 (regular shift) with only its own times
if ($schedule14 && $schedule14->shift && !$isOvernight14) {
    $aug14Times = $sofiaData['13']['2025-08-14'];
    echo "\nAug 14 available times: " . implode(', ', $aug14Times) . "\n";
    
    [$masuk14, $keluar14] = $findMethod->invoke($controller, $aug14Times, $schedule14->shift->start_time, $schedule14->shift->end_time, '2025-08-14');
    
    echo "Aug 14 Selected: Masuk={$masuk14}, Keluar={$keluar14}\n";
    echo "Aug 14 Expected: Masuk=2025-08-14 08:59:00, Keluar=2025-08-14 17:29:00\n";
}
