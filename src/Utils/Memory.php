<?php

namespace AMDarter\SimplyBackItUp\Utils;

class Memory
{
    /**
     * Converts memory limit from string (e.g., '512M') to bytes.
     *
     * @param string $memoryLimit The memory limit from ini_get('memory_limit').
     * @return int The memory limit in bytes.
     */
    public static function convertToBytes(string $memoryLimit): int
    {
        $unit = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int)$memoryLimit;

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }

    public static function availableMemory(): int
    {
        $memoryUsage = memory_get_usage();
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = self::convertToBytes($memoryLimit);
        return $memoryLimitBytes - $memoryUsage;
    }

    public static function safeBuffer(): int
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = Memory::convertToBytes($memoryLimit);
        return $memoryLimitBytes * 0.2; // Use only 20% of memory as a safe buffer
    }
}