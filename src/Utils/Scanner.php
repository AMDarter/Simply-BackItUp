<?php

namespace AMDarter\SimplyBackItUp\Utils;

use Respect\Validation\Validator as v;

class Scanner
{

    /**
     * List of dangerous file extensions for UNIX-based systems (Linux, macOS) and Windows.
     * @var array
     */
    public static $dangerousExtensions = [
        'exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'vbs', 'ps1', 'jar', 
        'wsf', 'hta', 'scr', 'pif', 'gadget', 'inf', 'reg', 'msp', 'scf', 'lnk'
    ];

    /**
     * Scan a directory for files.
     * @param string $path
     * @return \Generator
     * @throws \InvalidArgumentException
     */
    public static function scanFiles(string $path): \Generator
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Invalid directory path: {$path}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield $file->getRealPath();  // Yield file path instead of storing it in an array to save memory
            }
        }
    }

    /**
     * Check if a file has a dangerous extension.
     * @param string $filename
     * @return bool
     */
    public static function isDangerousExt(string $filename): bool
    {
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($file_extension, self::$dangerousExtensions)) {
            return true;
        }
        return v::executable()->validate($filename);
    }

    /**
     * Flag files with dangerous extensions.
     * @param string $path
     * @return array
     */
    public static function flagDangerousFileExtensions(string $path): array
    {
        $flaggedFiles = [];
        foreach (self::scanFiles($path) as $file) {
            if (self::isDangerousExt($file)) {
                $flaggedFiles[] = $file;
            }
        }
        return $flaggedFiles;
    }

    /**
     * Scan a ZIP file for dangerous file extensions.
     *
     * @param string $zipFilePath Path to the ZIP file.
     * @return array List of flagged dangerous files.
     * @throws \RuntimeException If the ZIP file cannot be opened.
     */
    public static function scanZipForDangerousFiles(string $zipFilePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \RuntimeException("Unable to open the ZIP file: {$zipFilePath}");
        }

        $flaggedFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (self::isDangerousExt($name)) {
                $flaggedFiles[] = $name;
            }
        }

        $zip->close();

        return $flaggedFiles;
    }

}
