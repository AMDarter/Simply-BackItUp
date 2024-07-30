<?php

namespace AMDarter;

class BackupEngine
{
    public function __construct()
    {
    }

    public function init()
    {
        // Start microtime
        $start = microtime(true);
        $this->log('Backup plugin initialized at exactly ' . (string) $start);

    }

    public function log(string $message)
    {
        $logFile = __DIR__ . '/logs/backup.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $log = fopen($logFile, 'a');
        if ($log === false) {
            error_log("Failed to open log file: $logFile");
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message" . PHP_EOL);
        fclose($log);
    }
}
