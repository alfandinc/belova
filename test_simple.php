<?php
// Simple test without Laravel bootstrap
echo "=== Testing Time Selection Logic ===\n";

// Test data that Sofia should have based on screenshot
$aug13Times = [
    '2025-08-13 15:58:44',
    '2025-08-13 16:00:13',
    '2025-08-14 00:00:30'  // This should be selected as keluar for overnight shift
];

$aug14Times = [
    '2025-08-14 08:59:00',  // This should be selected as masuk
    '2025-08-14 17:44:00',
    '2025-08-14 17:29:00'   // This should be selected as keluar (closer to 17:00)
];

// Simulate overnight shift logic
function isOvernightShift($start, $end) {
    return $start > $end;
}

function findBestAttendanceTimes($times, $shiftStart, $shiftEnd, $date) {
    if (empty($times)) {
        return [null, null];
    }

    $isOvernightShift = isOvernightShift($shiftStart, $shiftEnd);
    
    // Parse all available times
    $timeRecords = [];
    foreach ($times as $time) {
        $timestamp = strtotime($time);
        $timeOnly = date('H:i:s', $timestamp);
        $dateOnly = date('Y-m-d', $timestamp);
        
        $timeRecords[] = [
            'original' => $time,
            'timestamp' => $timestamp,
            'time_only' => $timeOnly,
            'date_only' => $dateOnly
        ];
    }

    // Sort by timestamp
    usort($timeRecords, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });

    $bestMasuk = null;
    $bestKeluar = null;
    
    if ($isOvernightShift) {
        // For overnight shifts, find jam masuk from shift date and jam keluar from next date
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
        
        // Find best jam masuk (from shift date, closest to shift start)
        $shiftDateTimes = array_filter($timeRecords, function($record) use ($date) {
            return $record['date_only'] === $date;
        });
        
        if (!empty($shiftDateTimes)) {
            $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
            $minMasukDiff = PHP_INT_MAX;
            
            foreach ($shiftDateTimes as $record) {
                $diff = abs($record['timestamp'] - $shiftStartTarget);
                if ($diff < $minMasukDiff) {
                    $minMasukDiff = $diff;
                    $bestMasuk = $record['original'];
                }
            }
        }
        
        // Find best jam keluar (from next date, closest to shift end)
        $nextDateTimes = array_filter($timeRecords, function($record) use ($nextDate) {
            return $record['date_only'] === $nextDate;
        });
        
        if (!empty($nextDateTimes)) {
            $shiftEndTarget = strtotime($nextDate . ' ' . $shiftEnd);
            $minKeluarDiff = PHP_INT_MAX;
            
            foreach ($nextDateTimes as $record) {
                $diff = abs($record['timestamp'] - $shiftEndTarget);
                if ($diff < $minKeluarDiff) {
                    $minKeluarDiff = $diff;
                    $bestKeluar = $record['original'];
                }
            }
        }
        
        // Fallbacks for overnight shifts
        if (!$bestMasuk && !empty($shiftDateTimes)) {
            $bestMasuk = end($shiftDateTimes)['original'];
        }
        
        if (!$bestKeluar && !empty($nextDateTimes)) {
            $bestKeluar = reset($nextDateTimes)['original'];
        }
        
    } else {
        // For regular shifts, find both times from the same date
        $sameDateTimes = array_filter($timeRecords, function($record) use ($date) {
            return $record['date_only'] === $date;
        });
        
        if (!empty($sameDateTimes)) {
            $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
            $shiftEndTarget = strtotime($date . ' ' . $shiftEnd);
            
            $minMasukDiff = PHP_INT_MAX;
            $minKeluarDiff = PHP_INT_MAX;
            
            foreach ($sameDateTimes as $record) {
                // Check for jam masuk
                $masukDiff = abs($record['timestamp'] - $shiftStartTarget);
                if ($masukDiff < $minMasukDiff) {
                    $minMasukDiff = $masukDiff;
                    $bestMasuk = $record['original'];
                }
                
                // Check for jam keluar
                $keluarDiff = abs($record['timestamp'] - $shiftEndTarget);
                if ($keluarDiff < $minKeluarDiff) {
                    $minKeluarDiff = $keluarDiff;
                    $bestKeluar = $record['original'];
                }
            }
        }
    }

    return [$bestMasuk, $bestKeluar];
}

// Test Aug 13 Malam shift (16:00-00:00) - overnight shift
echo "=== Aug 13 Malam Shift Test ===\n";
echo "Available times: " . implode(', ', $aug13Times) . "\n";
[$masuk13, $keluar13] = findBestAttendanceTimes($aug13Times, '16:00:00', '00:00:00', '2025-08-13');
echo "Selected: Masuk={$masuk13}, Keluar={$keluar13}\n";
echo "Expected: Masuk=2025-08-13 15:58:44, Keluar=2025-08-14 00:00:30\n";
echo "Test Pass: " . (($masuk13 === '2025-08-13 15:58:44' && $keluar13 === '2025-08-14 00:00:30') ? 'YES' : 'NO') . "\n\n";

// Test Aug 14 Pagi-Service shift (08:45-17:00) - regular shift
echo "=== Aug 14 Pagi-Service Shift Test ===\n";
echo "Available times: " . implode(', ', $aug14Times) . "\n";
[$masuk14, $keluar14] = findBestAttendanceTimes($aug14Times, '08:45:00', '17:00:00', '2025-08-14');
echo "Selected: Masuk={$masuk14}, Keluar={$keluar14}\n";
echo "Expected: Masuk=2025-08-14 08:59:00, Keluar=2025-08-14 17:29:00\n";
echo "Test Pass: " . (($masuk14 === '2025-08-14 08:59:00' && $keluar14 === '2025-08-14 17:29:00') ? 'YES' : 'NO') . "\n";
