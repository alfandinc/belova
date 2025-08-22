<?php
try {
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Create controller instance
    $controller = new App\Http\Controllers\HRD\AbsensiRekapController();

    // Test Sofia's Aug 13 Malam shift (16:00-00:00)
    echo "=== Testing Aug 13 Malam Shift ===\n";
    $aug13Times = [
        '2025-08-13 15:58:44',
        '2025-08-13 16:00:13', 
        '2025-08-14 00:00:30'
    ];

    // Use reflection to call private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('findBestAttendanceTimes');
    $method->setAccessible(true);

    $result13 = $method->invoke($controller, $aug13Times, '16:00:00', '00:00:00', '2025-08-13');
    echo "Available times: " . implode(', ', $aug13Times) . "\n";
    echo "Selected Masuk: " . ($result13[0] ?? 'NULL') . "\n";
    echo "Selected Keluar: " . ($result13[1] ?? 'NULL') . "\n";
    echo "Expected Masuk: 2025-08-13 15:58:44\n";
    echo "Expected Keluar: 2025-08-14 00:00:30\n";
    echo "Masuk OK: " . (($result13[0] ?? '') === '2025-08-13 15:58:44' ? 'YES' : 'NO') . "\n";
    echo "Keluar OK: " . (($result13[1] ?? '') === '2025-08-14 00:00:30' ? 'YES' : 'NO') . "\n\n";

    // Test Sofia's Aug 14 Pagi-Service shift (08:45-17:00)
    echo "=== Testing Aug 14 Pagi-Service Shift ===\n";
    $aug14Times = [
        '2025-08-14 08:59:00',
        '2025-08-14 17:44:00',
        '2025-08-14 17:29:00'
    ];

    $result14 = $method->invoke($controller, $aug14Times, '08:45:00', '17:00:00', '2025-08-14');
    echo "Available times: " . implode(', ', $aug14Times) . "\n";
    echo "Selected Masuk: " . ($result14[0] ?? 'NULL') . "\n";
    echo "Selected Keluar: " . ($result14[1] ?? 'NULL') . "\n";
    echo "Expected Masuk: 2025-08-14 08:59:00\n";
    echo "Expected Keluar: 2025-08-14 17:29:00\n";
    echo "Masuk OK: " . (($result14[0] ?? '') === '2025-08-14 08:59:00' ? 'YES' : 'NO') . "\n";
    echo "Keluar OK: " . (($result14[1] ?? '') === '2025-08-14 17:29:00' ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
