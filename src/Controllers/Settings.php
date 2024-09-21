<?php

namespace AMDarter\SimplyBackItUp\Controllers;

use Respect\Validation\Validator as v;

class Settings
{

    protected static function configs(): array
    {
        return [
            'backupFrequency' => [
                'validator' => v::optional(v::stringType()->in(['daily', 'weekly', 'monthly'])),
                'sanitizer' => 'sanitize_text_field',
                'option_name' => 'simply_backitup_frequency',
                'error_message' => 'Invalid frequency.',
            ],
            'backupTime' => [
                'validator' => v::optional(v::stringType()->regex('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/')),
                'sanitizer' => 'sanitize_text_field',
                'option_name' => 'simply_backitup_time',
                'error_message' => 'Invalid time.',
            ],
            'backupEmail' => [
                'validator' => v::optional(v::stringType()->email()),
                'sanitizer' => 'sanitize_email',
                'option_name' => 'simply_backitup_email',
                'error_message' => 'Invalid email address.',
            ],
            'backupStorageLocation' => [
                'validator' => v::optional(v::stringType()->in(['Google Drive', 'Dropbox', 'Amazon S3', 'OneDrive', 'FTP'])),
                'sanitizer' => 'sanitize_text_field',
                'option_name' => 'simply_backitup_backup_storage_location',
                'error_message' => 'Invalid backup storage location. Must be one of Google Drive, Dropbox, Amazon S3, OneDrive, or FTP.',
            ],
            'backupStorageCredentials' => [
                'validator' => v::arrayType(),
                'sanitizer' => [self::class, 'recursiveSanitizeText'],
                'option_name' => 'simply_backitup_backup_storage_credentials',
                'error_message' => 'Invalid backup storage credentials. Must be an array.' . gettype($postArray['backupStorageCredentials'] ?? '') . ' given.',
                'items' => [
                    'googleDriveApiKey' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Google Drive API key.',
                    ],
                    'googleDriveClientId' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Google Drive client ID.',
                    ],
                    'googleDriveClientSecret' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Google Drive client secret.',
                    ],
                    'dropboxAccessToken' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Dropbox access token.',
                    ],
                    'oneDriveClientId' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid OneDrive client ID.',
                    ],
                    'oneDriveClientSecret' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid OneDrive client secret.',
                    ],
                    'amazonS3AccessKey' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Amazon S3 access key.',
                    ],
                    'amazonS3SecretKey' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Amazon S3 secret key.',
                    ],
                    'amazonS3BucketName' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Amazon S3 bucket name.',
                    ],
                    'amazonS3Region' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid Amazon S3 region.',
                    ],
                    'ftpHost' => [
                        'validator' => v::optional(v::oneOf(v::ip(), v::url())),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid FTP host. Must be an IP address or URL.',
                    ],
                    'ftpUsername' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid FTP username.',
                    ],
                    'ftpPassword' => [
                        'validator' => v::optional(v::stringType()),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid FTP password.',
                    ],
                    'ftpPort' => [
                        'validator' => v::optional(v::digit()->intVal()->between(1, 65535)),
                        'sanitizer' => 'sanitize_text_field',
                        'error_message' => 'Invalid FTP port. Must be between 1 and 65535.',
                    ],
                ]
            ],
            'backupFiles' => [
                'validator' => v::optional(v::boolVal()),
                'option_name' => 'simply_backitup_backup_files',
                'error_message' => 'Invalid backup files value.',
            ],
            'backupDatabase' => [
                'validator' => v::optional(v::boolVal()),
                'option_name' => 'simply_backitup_backup_database',
                'error_message' => 'Invalid backup database value.',
            ],
            'backupPlugins' => [
                'validator' => v::optional(v::boolVal()),
                'option_name' => 'simply_backitup_backup_plugins',
                'error_message' => 'Invalid backup plugins value.',
            ],
            'backupThemes' => [
                'validator' => v::optional(v::boolVal()),
                'option_name' => 'simply_backitup_backup_themes',
                'error_message' => 'Invalid backup themes value.',
            ],
            'backupUploads' => [
                'validator' => v::optional(v::boolVal()),
                'option_name' => 'simply_backitup_backup_uploads',
                'error_message' => 'Invalid backup uploads value.',
            ],
        ];
    }

    public static function all(): array
    {
        $backupStorageCredentials = get_option('simply_backitup_backup_storage_credentials', []);

        // Sanitize storage credentials array if it is an array
        $sanitizedBackupStorageCredentials = is_array($backupStorageCredentials)
            ? array_map('sanitize_text_field', $backupStorageCredentials)
            : [];

        // Sanitize last backup time
        $lastBackupTime = get_option('simply_backitup_last_backup', null);
        if (is_string($lastBackupTime) && strtotime($lastBackupTime) !== false) {
            $lastBackupTime = sanitize_text_field($lastBackupTime);
        } else {
            $lastBackupTime = null;
        }

        return [
            'backupFrequency' => sanitize_text_field(
                get_option('simply_backitup_frequency', 'daily')
            ),
            'backupTime' => sanitize_text_field(
                get_option('simply_backitup_time', '03:00')
            ),
            'backupEmail' => sanitize_email(
                get_option('simply_backitup_email', '')
            ),
            'backupStorageLocation' => sanitize_text_field(
                get_option('simply_backitup_backup_storage_location', '')
            ),
            'backupStorageCredentials' => $sanitizedBackupStorageCredentials,
            'backupFiles' => rest_sanitize_boolean(
                get_option('simply_backitup_backup_files', true)
            ),
            'backupDatabase' => rest_sanitize_boolean(
                get_option('simply_backitup_backup_database', true)
            ),
            'backupPlugins' => rest_sanitize_boolean(
                get_option('simply_backitup_backup_plugins', true)
            ),
            'backupThemes' => rest_sanitize_boolean(
                get_option('simply_backitup_backup_themes', true)
            ),
            'backupUploads' => rest_sanitize_boolean(
                get_option('simply_backitup_backup_uploads', true)
            ),
            'lastBackupTime' => $lastBackupTime,
        ];
    }

    public static function index(): void
    {
        $settings = self::all();
        wp_send_json_success($settings);
    }

    public static function save(): void
    {
        $postArray = $_POST ?? [];

        $settingsConfig = self::configs();

        $validationErrors = [];
        $recordsToSave = [];

        foreach ($postArray as $key => $value) {
            if (!isset($settingsConfig[$key])) {
                continue;
            }
            $config = $settingsConfig[$key];
            $validator = $config['validator'];

            if (!$validator->validate($value)) {
                $validationErrors[$key] = $config['error_message'];
                continue;
            }
            if (isset($config['items'])) {
                foreach ($config['items'] as $itemKey => $itemConfig) {
                    $itemValue = $value[$itemKey] ?? null;
                    if (!is_object($itemConfig['validator'])) {
                        continue;
                    }
                    if (!method_exists($itemConfig['validator'], 'validate')) {
                        continue;
                    }
                    if (!$itemConfig['validator']->validate($itemValue)) {
                        $validationErrors[$itemKey] = $itemConfig['error_message'];
                        // Set the parent key as an error
                        $validationErrors[$key] = $config['error_message'];
                        // Remove the parent key from the records to save
                        unset($recordsToSave[$config['option_name']]);
                    }
                }
            }
            if (!empty($value) && array_key_exists('sanitizer', $config) && is_callable($config['sanitizer'])) {
                $sanitizedValue = $config['sanitizer']($value);
            } else {
                $sanitizedValue = $value;
            }
            // Success, save the settings.
            $recordsToSave[$config['option_name']] = $sanitizedValue;
        }

        if (!empty($validationErrors)) {
            wp_send_json_error([
                'message' => 'The form contains errors. Please correct them and try again.',
                'validationErrors' => $validationErrors
            ]);
            return;
        }

        if (!empty($recordsToSave)) {
            foreach ($recordsToSave as $optionName => $optionValue) {
                update_option($optionName, $optionValue);
            }
        }

        wp_send_json_success([
            'message' => 'Settings saved.'
        ]);
    }

    protected static function recursiveSanitizeText($data)
    {
        if (is_array($data)) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                $sanitizedData[$key] = self::recursiveSanitizeText($value);
            }
            return $sanitizedData;
        } elseif (is_object($data)) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                $sanitizedData[$key] = self::recursiveSanitizeText($value);
            }
            return $sanitizedData;
        }
        return sanitize_text_field($data);
    }
}
