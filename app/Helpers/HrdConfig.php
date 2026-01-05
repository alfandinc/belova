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
}
