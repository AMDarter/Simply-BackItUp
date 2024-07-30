<?php

namespace AMDarter;

class BackupEngine
{
    public function __construct()
    {
        
    }

    public function init()
    {
        $this->log('Backup plugin initialized at exactly ' . microtime(true));
        $this->log('Scanning started: ' . microtime(true));
        $files = $this->scanFiles(ABSPATH);
        $this->log('Scan complete: ' . microtime(true));
        $this->log('Files scanned: ' . count($files));
        $backupDir = ABSPATH . '../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupFile = $backupDir . '/backup-' . date('Y-m-d-H-i-s') . '.zip';
        $this->log('Backup file: ' . $backupFile);
        $this->log('Zipping started: ' . microtime(true));
        $this->zipDir(ABSPATH, $backupFile);
        $this->log('Zipping complete: ' . microtime(true));
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

    public function scanFiles(string $path): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getRealPath();
            }
        }
        return $files;
    }

    public function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathinfo($sourcePath);
        $dirName = $pathInfo['basename'];
        $z = new \ZipArchive();
        if ($z->open($outZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->log('Failed to create zip file at ' . $outZipPath);
            return false;
        }
        $this->folderToZip($sourcePath, $z, strlen("$sourcePath/"));
        $z->close();
        return true;
    }

    private function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..' && $f != '.git' && !$this->isDangerousExtension($f)) {
                $filePath = "$folder/$f";
                // Remove prefix from file path before adding to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    $this->folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    private function isDangerousExtension($filename)
    {
        $dangerous_extensions = ['exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'vbs', 'ps1', 'jar', 'wsf', 'hta', 'scr', 'pif', 'gadget', 'inf', 'reg', 'msp', 'scf', 'lnk'];
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($file_extension, $dangerous_extensions);
    }
}
