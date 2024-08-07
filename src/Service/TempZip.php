<?php

namespace AMDarter\SimplyBackItUp\Service;

use AMDarter\SimplyBackItUp\Utils\Scanner;

class TempZip
{
    public string $prefix = 'simply-backitup-wp-site-backup-';

    public function __construct()
    {   
    }

    public function generateFilename(): string
    {
        return $this->prefix . date('Y-m-d-H-i-s') . '.zip';
    }

    public function cleanup($maxAge = 1800)
    {
        $backupFiles = $this->list();
        $now = time();
        foreach ($backupFiles as $backupFile) {
            if (strpos($backupFile, $this->prefix) !== false) {
                $fileTime = filemtime($backupFile);
                $age = $now - $fileTime;
                if ($age > $maxAge) {
                    unlink($backupFile);
                }
            }
        }
    }

    public function list(): array
    {
        $tempDir = $this->tempDir();
        $backupFiles = glob((string) $tempDir . DIRECTORY_SEPARATOR . '*.zip');
        if (!is_array($backupFiles)) {
            return [];
        }
        return $backupFiles;
    }

    public function getFileNames(): array
    {
        $backupFiles = $this->list();
        $backupFileNames = [];
        foreach ($backupFiles as $backupFile) {
            $backupFileNames[] = basename($backupFile);
        }
        return $backupFileNames;
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
        $this->folderToZip($sourcePath, $zipArchive, strlen($sourcePath . DIRECTORY_SEPARATOR));
        $zipArchive->close();
    }

    private function folderToZip(string $folder, \ZipArchive &$zipArchive, int $exclusiveLength): void
    {
        $handle = opendir($folder);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..' && $f != '.git' && !Scanner::isDangerousExt($f)) {
                $filePath = $folder . DIRECTORY_SEPARATOR . $f;
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

    public function tempDir(): string
    {
        $tempBackupDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wordpress-backups';
        if (!is_dir($tempBackupDir)) {
            mkdir($tempBackupDir, 0755, true);
        }
        return $tempBackupDir;
    }

    public function extractDateFromTempZipFilename(string $filename): string
    {
        return str_replace([$this->prefix, '.zip'], '', basename($filename));
    }

    public function getMostRecent(): ?string
    {
        $backupFiles = $this->list();
        if (empty($backupFiles)) {
            return null;
        }
        if (count($backupFiles) === 1) {
            return $backupFiles[0];
        }
        usort($backupFiles, function ($a, $b) {
            $aDate = strtotime($this->extractDateFromTempZipFilename($a));
            $bDate = strtotime($this->extractDateFromTempZipFilename($b));
            return $aDate <=> $bDate;
        });
        $last = end($backupFiles);
        if ($last === false) {
            return null;
        }
        return $last;
    }

}
