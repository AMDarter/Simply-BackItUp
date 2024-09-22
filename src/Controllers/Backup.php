<?php

namespace AMDarter\SimplyBackItUp\Controllers;

use AMDarter\SimplyBackItUp\Service\TempZip;
use AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException;
use AMDarter\SimplyBackItUp\Validators\BackupValidator;
use AMDarter\SimplyBackItUp\Controllers\Settings;
use AMDarter\SimplyBackItUp\Utils\{
    Scanner,
    Memory
};
use Ifsnop\Mysqldump as IMysqldump;

class Backup
{

    public static function allHistory(): void
    {
        wp_send_json_success([
            'message' => 'History retrieved',
            'history' => get_option('simply_backitup_history', null)
        ]);
    }

    public static function clearHistory(): void
    {
        update_option('simply_backitup_history', []);
        wp_send_json_success(['message' => 'Logs cleared']);
    }

    protected static function logToHistory($message): void
    {
        $logs = get_option('simply_backitup_history', []);
        $date = date('Y-m-d H:i:s');
        array_unshift($logs, ['date' => $date, 'message' => $message]);
        update_option('simply_backitup_history', $logs);
        update_option('simply_backitup_last_backup', $date);
    }

    public static function step0(): void
    {
        if (!Memory::isEnoughMemory(50)) {
            wp_send_json_error(['message' => 'Not enough memory to safely create the backup. Ensure you have enough memory and try again.']);
        }

        try {
            $tempZipService = new TempZip();
            $tempZipService->cleanup(0);
            wp_send_json_success([
                'message' => 'Site health checked',
                'nextMessage' => 'Zipping files...',
                'progress' => 25
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => 'Failed to clean up temporary files. Check the error log for more information.']);
        }
    }

    public static function step1(): void
    {
        if (!Memory::isEnoughMemory(50)) {
            wp_send_json_error(['message' => 'Not enough memory to safely create the backup. Ensure you have enough memory and try again.']);
        }

        try {
            $tempStoreFile = self::zipFiles();
            set_transient('simply_backitup_temp_zip_file', $tempStoreFile, 30);
            wp_send_json_success([
                'message' => 'Files zipped',
                'nextMessage' => 'Exporting database...',
                'progress' => 50
            ]);
        } catch (InvalidBackupFileException $e) {
            error_log($e->getMessage());
            if (preg_match('/^(WARNING|DANGER)/', $e->getMessage())) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
            wp_send_json_error(['message' => 'Failed to zip files. Check the error log for more information.']);
        } catch (\Exception | \Error $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => 'Failed to zip files. Check the error log for more information.']);
        }
    }

    public static function step2(): void
    {
        if (!Memory::isEnoughMemory(50)) {
            wp_send_json_error(['message' => 'Not enough memory to safely create the backup. Ensure you have enough memory and try again.']);
        }

        try {
            $databaseExported = self::exportDatabase();
            if ($databaseExported) {
                wp_send_json_success([
                    'message' => 'Database exported',
                    'nextMessage' => 'Uploading to cloud server...',
                    'progress' => 75
                ]);
            } else {
                throw new \Exception('Database export failed');
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => 'Failed to export database. Check the error log for more information.']);
        }
    }

    public static function step3(): void
    {
        if (!Memory::isEnoughMemory(50)) {
            wp_send_json_error(['message' => 'Not enough memory to safely create the backup. Ensure you have enough memory and try again.']);
        }

        try {
            $tempBackupZipFile = self::getTransientBackupFile();
            self::uploadToCloud($tempBackupZipFile, Settings::all());
            $date = date('Y-m-d H:i:s');
            update_option('simply_backitup_last_backup', $date);
            $tempZipService = new TempZip();
            $tempZipService->cleanup(60);
            wp_send_json_success([
                'message' => 'Backup uploaded to cloud server',
                'progress' => 100,
                'backupTime' => $date
            ]);
        } catch (InvalidBackupFileException $e) {
            error_log(
                'Simply BackItUp: Failed to upload backup to cloud. ' . $e->getMessage()
            );
            if (preg_match('/^(WARNING|DANGER)/', $e->getMessage())) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
            wp_send_json_error(['message' => 'Failed to zip files. Check the error log for more information.']);
        } catch (\Exception $e) {
            error_log(
                'Simply BackItUp: Failed to upload backup to cloud. ' . $e->getMessage()
            );
            wp_send_json_error(['message' => 'Failed to upload backup to cloud. Check the error log for more details.']);
        }
    }

    public static function downloadBackupZip(): void
    {
        if (!Memory::isEnoughMemory(50)) {
            wp_send_json_error(['message' => 'Not enough memory to safely create the backup. Ensure you have enough memory and try again.']);
        }

        $tempBackupZipFile = "";
        // Check if the zip file is already created in the temp directory.
        try {
            $tempBackupZipFile = self::getTransientBackupFile();
        } catch (InvalidBackupFileException $e) {
            if (preg_match('/^(WARNING|DANGER)/', $e->getMessage())) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

        if (empty($tempBackupZipFile)) {
            try {
                $tempBackupZipFile = self::zipFiles();
            } catch (InvalidBackupFileException $e) {
                error_log($e->getMessage());
                wp_send_json_error(['message' => 'Unable to zip files. ' . $e->getMessage()]);
            } catch (\Exception | \Error $e) {
                error_log(
                    'Simply BackItUp: Failed to zip files. ' . $e->getMessage()
                );
                wp_send_json_error(['message' => 'Unable to zip files. An unexpected error occurred: ' . $e->getMessage()]);
            }
        }

        try {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($tempBackupZipFile) . '"');
            header('Content-Length: ' . filesize($tempBackupZipFile));
            header('Pragma: no-cache');
            header('Expires: 0');

            $currentUser = wp_get_current_user();
            self::logToHistory('Backup downloaded by user: ' . sanitize_user($currentUser->user_login));

            // Read in chunks
            $handle = fopen($tempBackupZipFile, 'rb');
            if ($handle === false) {
                throw new \Exception('Unable to open file.');
            }

            // Output file in chunks to prevent memory exhaustion
            while (!feof($handle)) {
                echo fread($handle, 1048576); // 1MB chunks
                flush(); // Ensure output is sent to the client immediately
            }

            fclose($handle);

            $tempZipService = new TempZip();
            $tempZipService->cleanup();
            exit;
        } catch (\Exception | \Error $e) {
            error_log(
                'Simply BackItUp: Failed to download backup. ' . $e->getMessage()
            );
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * @throws InvalidBackupFileException
     * @return string
     */
    protected static function zipFiles(): string
    {
        try {
            $tempZipService = new TempZip();
            $tempBackupZipFile = $tempZipService->tempDir() . DIRECTORY_SEPARATOR . $tempZipService->generateFilename();
            $tempZipService->zipDir(ABSPATH, $tempBackupZipFile);
            $validator = new BackupValidator($tempBackupZipFile);
            $validator->validateAll();
        } catch (\Exception $e) {
            error_log(
                'Simply BackItUp: Failed to zip files. ' . $e->getMessage()
            );
            throw $e;
        }

        set_transient(
            'simply_backitup_temp_zip_file',
            $tempBackupZipFile,
            30
        );
        return $tempBackupZipFile;
    }

    /**
     * @return string
     * @throws InvalidBackupFileException
     */
    protected static function getTransientBackupFile(): string
    {
        $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
        if (is_string($tempBackupZipFile)) {
            $tempBackupZipFile = wp_normalize_path($tempBackupZipFile);
        }
        $validator = new BackupValidator($tempBackupZipFile);
        $validator->validateFileName()
            ->validateFileExists()
            ->validateFileIsZip()
            ->validateFileSize()
            ->validateFileIsUnzippable();

        $time = time() - filemtime($tempBackupZipFile);
        if ($time < 30) {
            return $tempBackupZipFile;
        }
        return "";
    }

    protected static function exportDatabase(): bool
    {
        $tempZip = new TempZip();

        try {
            // Export the database to a SQL dump file.
            $dumpFile = $tempZip->tempDir() . DIRECTORY_SEPARATOR . 'simply-backitup-db-dump.sql';
            if (file_exists($dumpFile)) {
                unlink($dumpFile);
            }
            $dump = new IMysqldump\Mysqldump('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
            $dump->start($dumpFile);
        } catch (\Exception $e) {
            error_log('Simply BackItUp: Database export failed. ' . $e->getMessage());
            throw new \Exception('Database export failed.' . $e->getMessage());
        }

        try {
            // Add the SQL dump file to the zip file.
            $tempBackupZipFile = self::getTransientBackupFile();
            if (empty($tempBackupZipFile) || !file_exists($tempBackupZipFile)) {
                throw new \Exception('Temporary backup zip file not found or has expired.');
            }
            $tempZip->addFileToZip($dumpFile, $tempBackupZipFile);
            unlink($dumpFile);
        } catch (\Exception $e) {
            error_log('Simply BackItUp: Failed to add database dump to ZIP archive. ' . $e->getMessage());
            throw new \Exception('Failed to add database dump to ZIP archive. ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Upload the backup to the cloud storage.
     * @param string $file
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    private static function uploadToCloud(string $file, array $settings): bool
    {
        if (empty($settings['backupStorageLocation'])) {
            throw new \Exception('Backup storage location not set');
        }
        if (empty($settings['backupStorageCredentials'])) {
            throw new \Exception('Backup storage credentials not set');
        }
        if (empty($settings['backupFrequency'])) {
            throw new \Exception('Backup frequency not set');
        }
        if (empty($settings['backupTime'])) {
            throw new \Exception('Backup time not set');
        }

        // Implement your cloud upload logic here
        // Return true on success, false on failure
        sleep(5); // Simulate a task taking some time.
        self::logToHistory('Backup uploaded to ' . $settings['backupStorageLocation']);
        return true;
    }
}
