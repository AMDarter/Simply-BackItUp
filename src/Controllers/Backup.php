<?php

namespace AMDarter\SimplyBackItUp\Controllers;

use AMDarter\SimplyBackItUp\Service\TempZip;
use AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException;
use AMDarter\SimplyBackItUp\Validators\BackupValidator;
use AMDarter\SimplyBackItUp\Controllers\Settings;

class Backup
{

    public static function logs(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        $logs = get_option('simply_backitup_logs', []);
        wp_send_json_success(['logs' => $logs]);
    }

    public function clearLogs(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        update_option('simply_backitup_logs', []);
        wp_send_json_success(['message' => 'Logs cleared']);
    }

    protected static function log($message): void
    {
        $logs = get_option('simply_backitup_logs', []);
        $date = date('Y-m-d H:i:s');
        $logs[] = ['date' => $date, 'message' => $message];
        update_option('simply_backitup_logs', $logs);
        update_option('simply_backitup_last_backup', $date);
    }

    public static function step1(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        try {
            $tempStoreFile = self::zipFiles();
            set_transient('simply_backitup_temp_zip_file', $tempStoreFile, 120);
            wp_send_json_success(['message' => 'Files zipped', 'progress' => 33]);
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
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        try {
            $databaseExported = self::exportDatabase();
            if ($databaseExported) {
                wp_send_json_success(['message' => 'Database exported', 'progress' => 66]);
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
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');

        try {
            $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
            if (empty($tempBackupZipFile)) {
                throw new \Exception('Temporary backup file not found');
            }
            BackupValidator::validateBackupZipFile($tempBackupZipFile);
            $uploadedToCloud = self::uploadToCloud($tempBackupZipFile, Settings::all());
            if ($uploadedToCloud) {
                $date = date('Y-m-d H:i:s');
                update_option('simply_backitup_last_backup', $date);
                wp_send_json_success([
                    'message' => 'Backup uploaded to cloud',
                    'progress' => 100,
                    'backupTime' => $date
                ]);
            } else {
                throw new \Exception('Cloud upload failed');
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => 'Failed to upload backup to cloud. Check the error log for more details.']);
        }
    }

    public static function downloadBackupZip(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');

        // Check if the zip file is already created in the temp directory.
        $tempBackupZipFile = self::getTransientBackupFile();
        if (!$tempBackupZipFile) {
            try {
                $tempBackupZipFile = self::zipFiles();
            } catch (InvalidBackupFileException $e) {
                error_log($e->getMessage());
                wp_send_json_error(['message' => 'Unable to zip files. ' . $e->getMessage()]);
            } catch (\Exception | \Error $e) {
                error_log($e->getMessage());
                wp_send_json_error(['message' => 'Unable to zip files. An unexpected error occurred: ' . $e->getMessage()]);
            }
        }

        try {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($tempBackupZipFile) . '"');
            header('Content-Length: ' . filesize($tempBackupZipFile));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($tempBackupZipFile);
        } catch (\Exception | \Error $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        exit;
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
            BackupValidator::validateBackupZipFile($tempBackupZipFile);
        } catch (InvalidBackupFileException $e) {
            error_log($e->getMessage());
            throw $e;
        } catch (\Exception | \Error $e) {
            error_log($e->getMessage());
            throw new \Exception('Failed to zip files.' . $e->getMessage());
        }

        set_transient(
            'simply_backitup_temp_zip_file',
            $tempBackupZipFile,
            120 // 2 minutes
        );
        return $tempBackupZipFile;
    }

    protected static function getTransientBackupFile(): ?string
    {
        $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
        try {
            BackupValidator::validateBackupZipFile($tempBackupZipFile);
        } catch (InvalidBackupFileException $e) {
            return null;
        }
        $time = time() - filemtime($tempBackupZipFile);
        if ($time > 30) {
            $tempBackupZipFile = null;
        }
        return $tempBackupZipFile;
    }

    protected static function exportDatabase(): bool
    {
        // Implement your database export logic here
        // Return true on success, false on failure
        sleep(5); // Simulate a task taking some time
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
        self::log('Backup uploaded to ' . $settings['backupStorageLocation']);
        return true;
    }
}
