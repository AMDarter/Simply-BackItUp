<?php

namespace AMDarter\SimplyBackItUp\Validators;

use Respect\Validation\Validator as v;
use AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException;
use AMDarter\SimplyBackItUp\Utils\Scanner;

class BackupValidator
{

    /**
     * Validates the backup zip file.
     *
     * @param mixed $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return bool
     */
    public static function validateBackupZipFile($filename): bool
    {

        self::validateFileName($filename);
        self::validateFileExists($filename);
        self::validateFileIsReadable($filename);
        self::validateFileIsNotExecutable($filename);
        self::validateFileIsZip($filename);
        self::validateFileSize($filename);
        self::validateFileIsUnzippable($filename);
        self::validateZipIsNotEmpty($filename);
        self::validateZipContainsEssentialFiles($filename);
        self::validateDangerousFiles($filename);

        return true;
    }

    /**
     * Validates the file name is a non-empty string.
     *
     * @param mixed $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileName($filename): void
    {
        if (!is_string($filename) || empty($filename)) {
            throw new InvalidBackupFileException('The backup file name is not a valid string.');
        }
    }

    /**
     * Validates the file exists and is a regular file.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileExists(string $filename): void
    {
        if (!v::file()->validate($filename)) {
            throw new InvalidBackupFileException('The backup file does not exist or is not a regular file.');
        }
    }

    /**
     * Validates the file is readable.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileIsReadable(string $filename): void
    {
        if (!v::readable()->validate($filename)) {
            throw new InvalidBackupFileException('The backup file is not readable.');
        }
    }

    /**
     * Validates the file is not an executable.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileIsNotExecutable(string $filename): void
    {
        if (v::executable()->validate($filename)) {
            throw new InvalidBackupFileException('DANGER: The backup file is an executable. We advise you to take remediation steps immediately.');
        }
    }

    /**
     * Validates the file is a ZIP file.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileIsZip(string $filename): void
    {
        if (!v::extension('zip')->validate($filename)) {
            throw new InvalidBackupFileException('WARNING: The backup file is not a ZIP file.');
        }
    }

    /**
     * Validates the file size is within a reasonable range.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileSize(string $filename): void
    {
        if (!v::size(null, '500MB')->validate($filename)) {
            throw new InvalidBackupFileException('The backup file is too large. It must be less than 500MB.');
        }

        if (!v::size('20MB', null)->validate($filename)) {
            throw new InvalidBackupFileException('The backup file is too small. The zip is missing files.');
        }
    }

    /**
     * Validates that the ZIP file is unzippable.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateFileIsUnzippable(string $filename): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new InvalidBackupFileException('The backup file cannot be unzipped. It may be corrupted.');
        }
        $zip->close();
    }

    /**
     * Validates that the ZIP file is not empty.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateZipIsNotEmpty(string $filename): void
    {
        $zip = new \ZipArchive();
        $zip->open($filename);
        if ($zip->numFiles <= 0) {
            $zip->close();
            throw new InvalidBackupFileException('The backup file is empty.');
        }
        $zip->close();
    }

    /**
     * Validates that the ZIP file contains essential WordPress files and directories.
     *
     * @param string $filename
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     */
    private static function validateZipContainsEssentialFiles(string $filename): void
    {
        $essentialFiles = ['wp-config.php', 'index.php', 'wp-login.php'];
        $essentialDirs = ['wp-content', 'wp-includes', 'wp-admin'];

        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new InvalidBackupFileException('Unable to open the backup ZIP file.');
        }

        // Collect all file names and directory names from the ZIP archive
        $foundFiles = [];
        $foundDirs = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (substr($name, -1) === '/') {
                // It's a directory
                $foundDirs[] = rtrim($name, '/'); // Remove trailing slash for comparison
            } else {
                // It's a file
                $foundFiles[] = $name;
            }
        }

        $zip->close();

        // Normalize directory paths for comparison
        $foundDirs = array_map(function ($dir) {
            return trim($dir, '/'); // Remove any leading or trailing slashes
        }, $foundDirs);

        // Check for missing files
        $missingFiles = array_diff($essentialFiles, $foundFiles);

        // Check for missing directories
        $missingDirs = array_filter($essentialDirs, function ($requiredDir) use ($foundDirs) {
            // Check if the exact directory is not found
            return !in_array($requiredDir, $foundDirs, true);
        });

        if (!empty($missingFiles) || !empty($missingDirs)) {
            $missingItems = array_merge($missingFiles, $missingDirs);
            throw new InvalidBackupFileException('The backup ZIP file is missing the following essential files or directories: ' . implode(', ', $missingItems));
        }
    }

    private static function validateDangerousFiles($filename)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new InvalidBackupFileException('Unable to open the backup ZIP file.');
        }

        $flaggedFiles = Scanner::scanZipForDangerousFiles($filename);

        $zip->close();

        if (!empty($flaggedFiles)) {
            throw new InvalidBackupFileException('The backup ZIP file contains dangerous files: ' . implode(', ', $flaggedFiles));
        }
    }
}
