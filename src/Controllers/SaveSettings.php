<?php 

namespace AMDarter\SimplyBackItUp\Controllers;

use Respect\Validation\Validator as v;

class SaveSettings
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
        ];
    }

    public static function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        check_ajax_referer('simply_backitup_nonce', 'nonce');

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
                    if (!$itemConfig['validator']->validate($itemValue)) {
                        $validationErrors[$itemKey] = $itemConfig['error_message'];
                        // Set the parent key as an error
                        $validationErrors[$key] = $config['error_message'];
                        // Remove the parent key from the records to save
                        unset($recordsToSave[$config['option_name']]);
                    }
                }
            }
            if (!empty($value)) {
                $sanitizedValue = is_callable($config['sanitizer']) ? $config['sanitizer']($value) : $value;
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