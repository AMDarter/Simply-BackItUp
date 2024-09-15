<?php
/*
Plugin Name: Simply BackItUp
Plugin URI: http://yourwebsite.com/
Description: A plugin to backup your WordPress site.
Version: 1.0
Author: Anthony M. Darter
Author URI: http://yourwebsite.com/
License: MIT
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use AMDarter\SimplyBackItUp\Service\TempZip;
use Respect\Validation\Validator as v;

require_once __DIR__ . '/vendor/autoload.php';

define('SIMPLY_BACKITUP_ENV_MODE', 'DEVELOPMENT');

class SimplyBackItUp
{
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'onActivation']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_simply_backitup_save_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_simply_backitup_step1', [$this, 'backupStep1']);
        add_action('wp_ajax_simply_backitup_step2', [$this, 'backupStep2']);
        add_action('wp_ajax_simply_backitup_step3', [$this, 'backupStep3']);
    }

    public function onActivation(): void
    {
        if (!extension_loaded('zip')) {
            deactivate_plugins(plugin_basename(__FILE__));
            $error_message = 'The Backup Plugin could not be activated because it requires the ZipArchive extension, which is not loaded. Please enable the ZipArchive extension in your PHP configuration and try again.';
            wp_die($error_message, 'Plugin Activation Error', ['back_link' => true]);
        }
    }

    public function addAdminMenu(): void
    {
        add_menu_page('BackItUp', 'BackItUp', 'manage_options', 'simply-backitup', function () {
            echo '<div id="simply-backitup-settings"></div>';
        });
    }

    public function enqueueAdminScripts($hookSuffix): void
    {
        if ($hookSuffix === 'toplevel_page_simply-backitup') {
            $settings = [
                'backupFrequency' => get_option('simply_backitup_frequency', 'daily'),
                'backupTime' => get_option('simply_backitup_time', '03:00'),
                'backupEmail' => get_option('simply_backitup_email', ''),
                'backupStorageLocation' => get_option('simply_backitup_backup_storage_location', ''),
                'lastBackupTime' => get_option('simply_backitup_last_backup', null),
                'backupStorageCredentials' => get_option('simply_backitup_backup_storage_credentials', [])
            ];

            // Enqueue JS and CSS files dynamically
            $jsFiles = glob(plugin_dir_path(__FILE__) . 'src/Admin/dist/*.js');
            if (!empty($jsFiles)) {
                if (function_exists('wp_enqueue_module')) {
                    $wp_enqueue_module_function = "wp_enqueue_module";
                    $wp_enqueue_module_function(
                        'simply-backitup-admin-settings-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        [],
                        null
                    );
                } else {
                    wp_enqueue_script(
                        'simply-backitup-admin-settings-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        [],
                        null,
                        true
                    );
                    add_filter('script_loader_tag', function ($tag, $handle) {
                        if ($handle === 'simply-backitup-admin-settings-script') {
                            return str_replace(' src=', ' type="module" src=', $tag);
                        }
                        return $tag;
                    }, 10, 2);
                }
            }

            $cssFiles = glob(plugin_dir_path(__FILE__) . 'src/Admin/dist/*.css');
            if (!empty($cssFiles)) {
                wp_enqueue_style(
                    'simply-backitup-admin-settings-style',
                    plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($cssFiles[0]),
                    [],
                    null
                );
            }

            wp_localize_script('simply-backitup-admin-settings-script', 'SimplyBackItUp', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('simply_backitup_nonce'),
                'settings' => $settings
            ]);
        }
    }

    public function saveSettings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        check_ajax_referer('simply_backitup_nonce', 'nonce');

        $postArray = $_POST ?? [];

        $settingsConfig = [
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
                'sanitizer' => [$this, 'recursiveSanitizeText'],
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

    public function recursiveSanitizeText($data)
    {
        if (is_array($data)) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                $sanitizedData[$key] = $this->recursiveSanitizeText($value);
            }
            return $sanitizedData;
        } elseif (is_object($data)) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                $sanitizedData[$key] = $this->recursiveSanitizeText($value);
            }
            return $sanitizedData;
        }
        return sanitize_text_field($data);
    }

    public function backupStep1(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        try {
            $tempZipService = new TempZip();
            $tempBackupZipFile = $tempZipService->tempDir() . DIRECTORY_SEPARATOR . $tempZipService->generateFilename();
            $tempZipService->zipDir(ABSPATH, $tempBackupZipFile);
            set_transient('simply_backitup_temp_zip_file', $tempBackupZipFile, 3600);
            wp_send_json_success(['message' => 'Files zipped', 'progress' => 33]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function backupStep2(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        try {
            $databaseExported = $this->exportDatabase();
            if ($databaseExported) {
                wp_send_json_success(['message' => 'Database exported', 'progress' => 66]);
            } else {
                throw new \Exception('Database export failed');
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function backupStep3(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        try {
            $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
            if (!$tempBackupZipFile) {
                throw new \Exception('Temporary backup file not found');
            }
            $uploadedToCloud = $this->uploadToCloud($tempBackupZipFile);
            if ($uploadedToCloud) {
                delete_transient('simply_backitup_temp_zip_file');
                wp_send_json_success([
                    'message' => 'Backup uploaded to cloud',
                    'progress' => 100,
                    'backupTime' => get_option('simply_backitup_last_backup')
                ]);
            } else {
                throw new \Exception('Cloud upload failed');
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function downloadBackupZip(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
        if (!$tempBackupZipFile || !file_exists($tempBackupZipFile)) {
            wp_send_json_error(['message' => 'Backup file not found']);
            exit;
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($tempBackupZipFile) . '"');
        header('Content-Length: ' . filesize($tempBackupZipFile));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($tempBackupZipFile);
        delete_transient('simply_backitup_temp_zip_file');
        unlink($tempBackupZipFile);
        exit;
    }



    public function exportDatabase(): bool
    {
        // Implement your database export logic here
        // Return true on success, false on failure
        sleep(5); // Simulate a task taking some time
        return true;
    }

    public function uploadToCloud($file): bool
    {
        // Implement your cloud upload logic here
        // Return true on success, false on failure
        sleep(5); // Simulate a task taking some time
        update_option('simply_backitup_last_backup', date('Y-m-d H:i:s'));
        return true;
    }
}

new SimplyBackItUp();
