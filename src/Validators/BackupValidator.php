<?php

namespace AMDarter\SimplyBackItUp\Validators;

use Respect\Validation\Validator as v;
use AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException;
use AMDarter\SimplyBackItUp\Utils\Scanner;

class BackupValidator
{
    public $filename;

    public $missingCheckSumFiles;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Validates the backup zip file.
     *
     * @param array $knownChecksums An array of known checksums for essential WordPress files (the array keys represent the file paths).
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateAll(array $knownChecksums): self
    {
        return $this->validateFileName()
            ->validateFileExists()
            ->validateFileIsReadable()
            ->isDangerousFile()
            ->validateFileIsZip()
            ->validateFileSize()
            ->validateFileIsUnzippable()
            ->validateDangerousFiles()
            ->validateZipContainsEssentialFiles($knownChecksums);
    }

    /**
     * Validates the file name is a non-empty string.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileName(): self
    {
        if (!is_string($this->filename) || empty($this->filename) || !v::file()->validate($this->filename)) {
            throw new InvalidBackupFileException('The backup file name is not a valid file name.');
        }
        return $this;
    }

    /**
     * Validates the file exists and is a regular file.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileExists(): self
    {
        if (!v::file()->validate($this->filename)) {
            throw new InvalidBackupFileException('The backup file does not exist or is not a regular file.');
        }
        return $this;
    }

    /**
     * Validates the file is readable.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileIsReadable(): self
    {
        if (!v::readable()->validate($this->filename)) {
            throw new InvalidBackupFileException('The backup file is not readable.');
        }
        return $this;
    }

    /**
     * Validates the file is not an executable or is dangerous.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function isDangerousFile(): self
    {
        if (Scanner::isDangerousExt($this->filename)) {
            throw new InvalidBackupFileException(
                'DANGER: The backup file is not safe to download. We advise you to take remediation steps immediately.'
            );
        }
        return $this;
    }

    /**
     * Validates the file is a ZIP file.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileIsZip(): self
    {
        if (!v::extension('zip')->validate($this->filename)) {
            throw new InvalidBackupFileException('WARNING: The backup file is not a ZIP file.');
        }
        return $this;
    }

    /**
     * Validates the file size is within a reasonable range.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileSize(): self
    {
        if (!v::size(null, '1GB')->validate($this->filename)) {
            throw new InvalidBackupFileException('The backup file is too large. It must be less than 1GB.');
        }

        if (!v::size('20MB', null)->validate($this->filename)) {
            throw new InvalidBackupFileException('The backup file is too small. The zip is missing files.');
        }
        return $this;
    }

    /**
     * Validates that the ZIP file is unzippable.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateFileIsUnzippable(): self
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filename) !== true) {
            throw new InvalidBackupFileException('The backup file cannot be unzipped. It may be corrupted.');
        }
        $zip->close();
        return $this;
    }

    /**
     * Validates that the ZIP file contains essential WordPress files and that they exist in the WordPress known Checksums API.
     *
     * @param array $knownChecksums An array of known checksums for essential WordPress files (the array keys represent the file paths).
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateZipContainsEssentialFiles(array $knownChecksums): self
    {
        if (empty($knownChecksums)) {
            return $this;
        }

        $replaceMultipleSlashes = function ($file) {
            return preg_replace('|(?<=.)/+|', '/', $file);
        };

        $zip = new \ZipArchive();
        if ($zip->open($this->filename) !== true) {
            throw new InvalidBackupFileException('Unable to open the backup ZIP file.');
        }

        // Collect all file names from the ZIP archive
        $foundFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $replaceMultipleSlashes($stat['name']);

            // Ensure we only add files, not directories
            if (substr($stat['name'], -1) !== '/' && $stat['size'] > 0) {
                $foundFiles[] = $name;
            }
        }

        $essentialFiles = array_map($replaceMultipleSlashes, array_keys($knownChecksums));
        $essentialFilesCount = count($essentialFiles);

        // Check if all essential files are present in found files
        $missingFiles = [];
        for ($i = 0; $i < $essentialFilesCount; $i++) {
            $file = $essentialFiles[$i];
            // Does it actually exist? Some admins may have custom paths or removed core WP files intentionally.
            if (!file_exists(ABSPATH . $file)) {
                continue;
            }
            // Check if the file is missing from the ZIP
            if (!in_array($file, $foundFiles, true)) {
                $missingFiles[] = $file;
            }
        }

        $essentialFiles = null; // Free up memory

        if (!empty($missingFiles)) {
            $zip->close();
            $this->missingCheckSumFiles = $missingFiles;
            $count = count($missingFiles);
            throw new InvalidBackupFileException("The backup ZIP file is missing $count essential WordPress files.");
        }

        $zip->close();
        return $this;
    }

    /**
     * Validates that the ZIP file does not contain dangerous files.
     *
     * @throws \AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException
     * @return self
     */
    public function validateDangerousFiles(): self
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filename) !== true) {
            throw new InvalidBackupFileException('Unable to open the backup ZIP file.');
        }

        $flaggedFiles = Scanner::scanZipForDangerousFiles($this->filename);

        $zip->close();

        if (!empty($flaggedFiles)) {
            throw new InvalidBackupFileException('DANGER: The backup ZIP file contains dangerous files: ' . implode(', ', $flaggedFiles));
        }
        return $this;
    }
}
