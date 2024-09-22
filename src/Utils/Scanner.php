<?php

namespace AMDarter\SimplyBackItUp\Utils;

use Respect\Validation\Validator as v;

class Scanner
{

    /**
     * List of dangerous file extensions for UNIX-based systems (Linux, macOS) and Windows.
     * @var array
     */
    public static $dangerousExtensions = ['exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'bin', 'msi', 'vbs', 'ps1', 'jar', 'wsf', 'hta', 'scr', 'pif', 'gadget', 'inf', 'reg', 'msp', 'scf', 'lnk'];

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

    /**
     * Fetches checksums from the WordPress API for the current WordPress version and locale.
     * Caches the result in WordPress object cache to avoid multiple requests.
     *
     * @return array|null Associative array of known checksums or null if the API request fails.
     */
    public static function getChecksumsFromApi(): ?array
    {
        $version = get_bloginfo('version');
        $locale = get_locale();

        $cacheKey = "wp_checksums_{$version}_{$locale}";

        $cachedChecksums = wp_cache_get($cacheKey);
        if ($cachedChecksums !== false) {
            return $cachedChecksums;
        }

        $url = "https://api.wordpress.org/core/checksums/1.0/?version={$version}&locale={$locale}";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $checksums = $data['checksums'] ?? null;

        if (!is_array($checksums) || empty($checksums)) {
            return null;
        }

        wp_cache_set(
            $cacheKey,
            $checksums,
            '',
            2 * MONTH_IN_SECONDS // Use Memcache or Redis to cache this for 60 days.
        );

        return $checksums;
    }

    /**
     * Verify file integrity by comparing file hashes with known good MD5 hashes (from the WordPress API).
     *
     * @param string $path Path to the directory to scan.
     * @param array $knownChecksums Associative array of known MD5 checksums ['file path' => 'expected md5 hash'].
     * @return array List of files with mismatched checksums.
     */
    public static function verifyChecksums(array $knownChecksums): array
    {
        $mismatchedFiles = [];
        $path = ABSPATH;

        foreach (self::scanFiles($path) as $file) {
            $relativeFilePath = str_replace($path, '', $file);

            if (isset($knownChecksums[$relativeFilePath])) {
                $currentHash = md5_file($file);

                $expectedHash = $knownChecksums[$relativeFilePath];

                if ($currentHash !== $expectedHash) {
                    $mismatchedFiles[] = [
                        'file' => $relativeFilePath,
                        'expected' => $expectedHash,
                        'found' => $currentHash,
                    ];
                }
            }
        }

        return $mismatchedFiles;
    }
}
