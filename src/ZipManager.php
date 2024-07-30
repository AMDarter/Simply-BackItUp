<?php

namespace AMDarter;

class ZipManager
{
    public string $tempZipFilePrefix = 'amdarter-wp-site-backup-';


    public function __construct()
    {
    }

    public function generateTempZipFilename(): string
    {
        return $this->tempZipFilePrefix . date('Y-m-d-H-i-s') . '.zip';
    }

    public function cleanupTempZips($maxAge = 1800)
    {
        $backupFiles = $this->listTempBackupZips();
        $now = time();
        foreach ($backupFiles as $backupFile) {
            if (strpos($backupFile, $this->tempZipFilePrefix) !== false) {
                $fileTime = filemtime($backupFile);
                $age = $now - $fileTime;
                if ($age > $maxAge) {
                    unlink($backupFile);
                }
            }
        }
    }

    public function listTempBackupZips(): array
    {
        $tempBackupDir = $this->tempBackupDir();
        $backupFiles = glob((string) $tempBackupDir . '/*.zip');
        if (!is_array($backupFiles)) {
            return [];
        }
        return $backupFiles;
    }

    public function getTempBackupZipFileNames(): array
    {
        $backupFiles = $this->listTempBackupZips();
        $backupFileNames = [];
        foreach ($backupFiles as $backupFile) {
            $backupFileNames[] = basename($backupFile);
        }
        return $backupFileNames;
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

    /**
     * @throws \Exception
     */
    public function zipDir(string $sourcePath, string $outZipPath): void
    {
        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($outZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP archive at $outZipPath");
        }
        $this->folderToZip($sourcePath, $zipArchive, strlen("$sourcePath/"));
        $zipArchive->close();
    }

    private function folderToZip(string $folder, \ZipArchive &$zipArchive, int $exclusiveLength): void
    {
        $handle = opendir($folder);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..' && $f != '.git' && !$this->isDangerousExtension($f)) {
                $filePath = "$folder/$f";
                // Remove prefix from file path before adding to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipArchive->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipArchive->addEmptyDir($localPath);
                    $this->folderToZip($filePath, $zipArchive, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    public function tempBackupDir(): string
    {
        $tempBackupDir = sys_get_temp_dir() . '/wordpress-backups';
        if (!is_dir($tempBackupDir)) {
            mkdir($tempBackupDir, 0755, true);
        }
        return $tempBackupDir;
    }

    public function isDangerousExtension(string $filename): bool
    {
        $dangerous_extensions = ['exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'vbs', 'ps1', 'jar', 'wsf', 'hta', 'scr', 'pif', 'gadget', 'inf', 'reg', 'msp', 'scf', 'lnk'];
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($file_extension, $dangerous_extensions);
    }

    public function extractDateFromTempFilename(string $filename): string
    {
        return str_replace([$this->tempZipFilePrefix, '.zip'], '', basename($filename));
    }

    public function getMostRecentTempZip(): ?string
    {
        $backupFiles = $this->listTempBackupZips();
        if (empty($backupFiles)) {
            return null;
        }
        if (count($backupFiles) === 1) {
            return $backupFiles[0];
        }
        // Find the newest backup file by sorting the list of files by date.
        usort($backupFiles, function ($a, $b) {
            $aDate = strtotime($this->extractDateFromTempFilename($a));
            $bDate = strtotime($this->extractDateFromTempFilename($b));
            return $aDate <=> $bDate;
        });
        $last = end($backupFiles);
        if ($last === false) {
            return null;
        }
        return $last;
    }
}
