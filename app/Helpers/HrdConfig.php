<?php

namespace App\Helpers;

class HrdConfig
{
    protected static function filePath(): string
    {
        return storage_path('app/hrd_config.json');
    }

    protected static function readAll(): array
    {
        $path = self::filePath();
        if (!file_exists($path)) {
            return [];
        }
        try {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected static function writeAll(array $data): void
    {
        $path = self::filePath();
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public static function getLeaveDailyCapacity(): int
    {
        $data = self::readAll();
        $val = isset($data['leave_daily_capacity']) ? (int)$data['leave_daily_capacity'] : 2;
        return $val > 0 ? $val : 2;
    }

    public static function setLeaveDailyCapacity(int $capacity): void
    {
        $capacity = max(1, $capacity);
        $data = self::readAll();
        $data['leave_daily_capacity'] = $capacity;
        self::writeAll($data);
    }

    /** Y-m-d from which worked Sundays / holidays count for ganti libur (older data is ignored). */
    public static function getGantiLiburMulai(): string
    {
        $val = self::readAll()['ganti_libur_mulai'] ?? null;

        return is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ? $val : '2026-07-01';
    }

    /** Minimum staff per shift per day: [shift_id => int]. Days below it are flagged in the schedule grid. */
    public static function getShiftMinStaff(): array
    {
        $val = self::readAll()['shift_min_staff'] ?? [];

        return is_array($val) ? array_map('intval', $val) : [];
    }

    public static function setShiftMinStaff(int $shiftId, int $min): void
    {
        $data = self::readAll();
        $mins = is_array($data['shift_min_staff'] ?? null) ? $data['shift_min_staff'] : [];
        if ($min > 0) {
            $mins[$shiftId] = $min;
        } else {
            unset($mins[$shiftId]);
        }
        $data['shift_min_staff'] = $mins;
        self::writeAll($data);
    }
}
