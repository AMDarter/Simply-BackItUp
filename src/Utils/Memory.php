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

    /**
     * Check if available memory is greater than a specified buffer.
     *
     * @param int $safeBufferMB The safe buffer size in megabytes.
     * @return bool True if enough memory is available, false if not.
     */
    public static function isEnoughMemory(int $safeBufferMB): bool
    {
        $safeBufferBytes = $safeBufferMB * 1024 * 1024; // Convert MB to bytes
        $availableMemory = self::availableMemory();

        return $availableMemory >= $safeBufferBytes;
    }
}
