<?php

namespace AMDarter\SimplyBackItUp\Service;

use AMDarter\SimplyBackItUp\Utils\{
    Memory,
    Scanner
};

class TempZip
{

    private bool $excludeDangerousExtensions = true;

    private array $customExclusions = ['.', '..'];

    /**
     * @var string Prefix used for backup ZIP filenames.
     */
    private string $prefix = 'simply-backitup-wp-site-backup-';

    public function __construct() {}

    public function setExcludeDangerousExtensions(bool $excludeDangerousExtensions): void
    {
        $this->excludeDangerousExtensions = $excludeDangerousExtensions;
    }

    public function setCustomExclusions(array $customExclusions): void
    {
        $this->customExclusions = $customExclusions;
    }

    /**
     * Generates a unique filename for the ZIP backup file.
     * 
     * @return string The generated filename for the ZIP archive, formatted as: 'simply-backitup-wp-site-backup-YYYY-MM-DD-HH-MM-SS.zip'.
     */
    public function generateFilename(): string
    {
        return $this->prefix . date('Y-m-d-H-i-s') . '.zip';
    }

    /**
     * Cleans up old backup files by removing those that exceed the specified age.
     *
     * @param int $maxAge Maximum age of files (in seconds) before they are deleted. Default is 3 seconds.
     * @return void
     */
    public function cleanup($maxAge = 3): void
    {
        $backupFiles = $this->list();
        foreach ($backupFiles as $backupFile) {
            if (strpos($backupFile, $this->prefix) !== false) {
                $age = time() - filemtime($backupFile);
                if ($age > $maxAge) {
                    unlink($backupFile);
                }
            }
        }
    }

    /**
     * Retrieves a list of all backup files in the temporary backup directory.
     *
     * @return array An array of filenames that match the backup ZIP pattern.
     */
    public function list(): array
    {
        $tempDir = $this->tempDir();
        $backupFiles = glob((string) $tempDir . DIRECTORY_SEPARATOR . '*.zip');
        if (!is_array($backupFiles)) {
            return [];
        }
        return $backupFiles;
    }

    public function sanitizeFileName(string $filename): string
    {
        return sanitize_file_name($filename);
    }

    /**
     * Recursively calculates the total size of the directory.
     *
     * @param string $directory The path of the directory.
     * @param int $maxSizeThreshold The maximum size threshold in bytes.
     * @param int $maxDepth The maximum depth to traverse.
     * @return int The total size of the directory in bytes.
     */
    private function getDirSize(string $directory, int $maxSizeThreshold, int $maxDepth = 25): int
    {
        $size = 0;

        $dirIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($dirIterator as $file) {
            // If we have a max depth, skip files beyond that depth
            if ($dirIterator->getDepth() > $maxDepth) {
                continue;
            }
            $size += $file->getSize();
            if ($size > $maxSizeThreshold) {
                // Stop calculating size if it exceeds the threshold. This is a performance optimization.
                break;
            }
        }

        return $size;
    }

    /**
     * Check if the directory is larger than the specified size limit.
     *
     * @param string $directory The path of the directory to check.
     * @param int $sizeLimitBytes The size limit in bytes.
     * @throws \Exception If the directory exceeds the size limit.
     * @return bool true if too large, false if not.
     */
    public function excedesSizeLimit(string $directory, int $sizeLimitBytes): bool
    {
        $directorySize = $this->getDirSize($directory, $sizeLimitBytes);

        if ($directorySize > $sizeLimitBytes) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the directory has enough storage space to create a ZIP archive.
     * @param string $directory
     * @param int $requiredSpace
     * @return bool
     */
    protected static function hasEnoughStorage(string $directory, int $requiredSpace): bool
    {
        $availableSpace = disk_free_space($directory); // Gets available space in bytes
        return $availableSpace >= $requiredSpace; // Compare available space with required space
    }

    /**
     * Creates a ZIP archive of a specified directory.
     *
     * @param string $sourcePath The path to the directory that will be archived.
     * @param string $outZipPath The output path where the ZIP file will be saved.
     * @throws \Exception If the ZIP archive cannot be created.
     * @return void
     */
    public function zipDir(string $sourcePath, string $outZipPath): void
    {
        $sizeLimitBytes = disk_free_space($sourcePath);
        if ($sizeLimitBytes === false) {
            throw new \Exception("Failed to determine free space on disk.");
        }
        error_log('Free space: ' . $sizeLimitBytes);
        $excedesSizeLimit = $this->excedesSizeLimit($sourcePath, $sizeLimitBytes);
        $sizeLimitMb = $sizeLimitBytes / 1024 / 1024;

        if ($excedesSizeLimit) {
            throw new \Exception("The directory size exceeds the limit of $sizeLimitMb MB.");
        }

        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($outZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP archive at $outZipPath");
        }

        $this->folderToZip($sourcePath, $zipArchive, strlen($sourcePath . DIRECTORY_SEPARATOR));
        $zipArchive->close();
    }

    private function shouldExcludeFromZip(string $file): bool
    {
        if (in_array($file, $this->customExclusions)) {
            return true;
        }

        if ($this->excludeDangerousExtensions && Scanner::isDangerousExt($file)) {
            return true;
        }

        return false;
    }

    /**
     * Recursively adds files and directories from a source folder to a ZIP archive.
     * 
     * This method processes each directory and file individually, maintaining stable memory usage. 
     * PHP effectively releases memory between recursive calls, preventing spikes. 
     * The memory footprint remains low since no large data structures are retained across iterations.
     *
     * @param string $folder The directory to add to the ZIP archive.
     * @param \ZipArchive $zipArchive The active ZIP archive object.
     * @param int $exclusiveLength The number of characters in the base directory path to exclude from the archived file paths, so the ZIP contains relative paths.
     * @throws \Exception If the directory cannot be opened.
     * @return void
     */
    private function folderToZip(string $folder, \ZipArchive &$zipArchive, int $exclusiveLength): void
    {
        $handle = opendir($folder);
        if ($handle === false) {
            $zipArchive->close();
            throw new \Exception("Unable to open directory $folder for zipping.");
        }

        // Iterate through directory contents and add them to the ZIP.
        while (false !== ($f = readdir($handle))) {
            if ($this->shouldExcludeFromZip($f)) {
                continue; // Skip
            }

            $filePath = $folder . '/' . $f;
            // Determine the relative file path to add to the ZIP archive.
            $localPath = substr($filePath, $exclusiveLength);

            if (is_file($filePath)) {
                $zipArchive->addFile($filePath, $localPath); // Add file to the archive
            } elseif (is_dir($filePath)) {
                // Recursively add directories
                $zipArchive->addEmptyDir($localPath);
                $this->folderToZip($filePath, $zipArchive, $exclusiveLength);
            }
        }

        closedir($handle);
    }

    /**
     * Returns the temporary backup directory path where the ZIP files are stored.
     * Creates the directory if it does not exist.
     *
     * @return string The path to the temporary backup directory.
     */
    public function tempDir(): string
    {
        $tempBackupDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wordpress-backups';
        if (!is_dir($tempBackupDir)) {
            mkdir($tempBackupDir, 0755, true);
        }
        return $tempBackupDir;
    }
}
