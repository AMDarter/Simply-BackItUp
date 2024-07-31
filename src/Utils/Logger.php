<?php

namespace AMDarter\SimplyBackItUp\Admin;

class Logger
{
    public string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function log(string $message)
    {
        $logFile = $this->logFile;
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $log = fopen($logFile, 'a');
        if ($log === false) {
            error_log("Failed to open log file: $logFile");
            return;
        }
        if ($this->isMaxFileSize()) {
            $this->clear();
        }
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message" . PHP_EOL);
        fclose($log);
    }

    public function isMaxFileSize( int $maxSize = 1000000): bool
    {
        return filesize($this->logFile) > $maxSize;
    }

    public function clear()
    {
        file_put_contents($this->logFile, '');
    }
}
