<?php

namespace AMDarter\SimplyBackItUp\Utils;

class Scanner
{

    public static function scanFiles(string $path): array
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

    public static function fileCount(string $path): int
    {
        return count(self::scanFiles($path));
    }

    public static function isDangerousExt(string $filename): bool
    {
        $dangerous_extensions = ['exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'vbs', 'ps1', 'jar', 'wsf', 'hta', 'scr', 'pif', 'gadget', 'inf', 'reg', 'msp', 'scf', 'lnk'];
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($file_extension, $dangerous_extensions);
    }

    public static function flagDangerousFileExtensions(string $path): array
    {
        $files = self::scanFiles($path);
        $flaggedFiles = [];
        foreach ($files as $file) {
            if (self::isDangerousExt($file)) {
                $flaggedFiles[] = $file;
            }
        }
        return $flaggedFiles;
    }
}
