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

require_once __DIR__ . '/vendor/autoload.php';

define('SIMPLY_BACKITUP_ENV_MODE', 'DEVELOPMENT');

register_activation_hook(__FILE__, function () {
    if (!extension_loaded('zip')) {
        deactivate_plugins(plugin_basename(__FILE__));
        $error_message = 'The Backup Plugin could not be activated because it requires the ZipArchive extension, which is not loaded. Please enable the ZipArchive extension in your PHP configuration and try again.';
        wp_die($error_message, 'Plugin Activation Error', ['back_link' => true]);
    }
});

add_action('admin_menu', function () {
    add_menu_page('BackItUp', 'BackItUp', 'manage_options', 'simply-backitup', function () {
        echo '<div id="simply-backitup-settings"></div>';
    });
});

add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if ($hook_suffix === 'toplevel_page_simply-backitup') {
        $settings = [
            'backup-frequency' => get_option('simply_backitup_frequency', 'daily'),
            'backup-time' => get_option('simply_backitup_time', '03:00'),
            'backup-email' => get_option('simply_backitup_email', ''),
            'backup-storage-location' => get_option('simply_backitup_backup_storage_location', ''),
            'last-backup-time' => get_option('simply_backitup_last_backup', null),
            'backup-storage-credentials' => get_option('simply_backitup_backup_storage_credentials', [])
        ];
        $js_files = glob((string) plugin_dir_path(__FILE__) . 'src/Admin/dist/*.js');
        if (!empty($js_files)) {
            wp_enqueue_script(
                'simply-backitup-admin-settings-script',
                plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($js_files[0]),
                [],
                null,
                true
            );
        }
        $css_files = glob((string) plugin_dir_path(__FILE__) . 'src/Admin/dist/*.css');
        if (!empty($css_files)) {
            wp_enqueue_style(
                'simply-backitup-admin-settings-style',
                plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($css_files[0]),
                [],
                null,
            );
        }
        wp_localize_script('simply-backitup-admin-settings-script', 'SimplyBackItUp', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simply_backitup_nonce'),
            'settings' => $settings
        ]);
    }
});

function simplyBackItUpRecursiveSanitizeText($data)
{
    if (is_array($data)) {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            $sanitizedData[$key] = simplyBackItUpRecursiveSanitizeText($value);
        }
        return $sanitizedData;
    } else if (is_object($data)) {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            $sanitizedData[$key] = simplyBackItUpRecursiveSanitizeText($value);
        }
        return $sanitizedData;
    }
    return sanitize_text_field($data);
}

add_action('wp_ajax_simply_backitup_save_settings', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }

    check_ajax_referer('simply_backitup_nonce', 'nonce');

    foreach ($_POST as $key => $value) {
        switch ($key) {
            case 'backup-frequency':
                if (!is_string($value)) {
                    wp_send_json_error('Invalid frequency.');
                    return;
                }
                $frequency = sanitize_text_field($value);
                if (!in_array($frequency, ['daily', 'weekly', 'monthly'])) {
                    wp_send_json_error('Invalid frequency.');
                    return;
                }
                update_option('simply_backitup_frequency', $frequency);
                break;
            case 'backup-time':
                if (!is_string($value)) {
                    wp_send_json_error('Invalid time.');
                    return;
                }
                $time = sanitize_text_field($value);
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                    wp_send_json_error('Invalid time.');
                    return;
                }
                update_option('simply_backitup_time', $time);
                break;
            case 'backup-email':
                if (!is_string($value)) {
                    wp_send_json_error('Invalid email address.');
                    return;
                }
                $email = sanitize_email($value);
                if (!is_email($email)) {
                    wp_send_json_error('Invalid email address.');
                    return;
                }
                update_option('simply_backitup_email', $email);
                break;
            case 'backup-storage-location':
                if (!is_string($value)) {
                    wp_send_json_error('Invalid storage location.');
                    return;
                }
                $backupStorageLocation = sanitize_text_field($value);
                if (!in_array($backupStorageLocation, ['Google Drive', 'Dropbox', 'Amazon S3', 'OneDrive', 'FTP'])) {
                    wp_send_json_error('Invalid backup storage location.');
                    return;
                }
                update_option('simply_backitup_backup_storage_location', $backupStorageLocation);
                break;
            case 'backup-storage-credentials':
                if (!is_string($value)) {
                    wp_send_json_error('Invalid credentials.');
                    return;
                }
                if (empty($value)) {
                    $value = '{}';
                }
                $backupStorageCredentials = simplyBackItUpRecursiveSanitizeText(json_decode(stripslashes($value), true));
                // @todo Validate credentials based on storage location.
                update_option('simply_backitup_backup_storage_credentials', $backupStorageCredentials);
                break;
        }
    }
    wp_send_json_success();
});

add_action('wp_ajax_simply_backitup_step1', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    check_ajax_referer('simply_backitup_nonce', 'nonce');
    wp_send_json_error(['message' => 'Testing failure']);
    return;
    try {
        // Step 1: Zip up files
        $tempZipService = new TempZip();
        $tempBackupZipFile = $tempZipService->tempDir() . DIRECTORY_SEPARATOR . $tempZipService->generateFilename();
        $tempZipService->zipDir(ABSPATH, $tempBackupZipFile);
        set_transient('simply_backitup_temp_zip_file', $tempBackupZipFile, 3600);
        wp_send_json_success(['message' => 'Files zipped', 'progress' => 33]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

add_action('wp_ajax_simply_backitup_step2', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    check_ajax_referer('simply_backitup_nonce', 'nonce');
    try {
        // Step 2: Export database
        $databaseExported = export_database(); // Implement this function
        if ($databaseExported) {
            wp_send_json_success(['message' => 'Database exported', 'progress' => 66]);
        } else {
            throw new Exception('Database export failed');
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

add_action('wp_ajax_simply_backitup_step3', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    check_ajax_referer('simply_backitup_nonce', 'nonce');
    try {
        $tempBackupZipFile = get_transient('simply_backitup_temp_zip_file');
        if (!$tempBackupZipFile) {
            throw new Exception('Temporary backup file not found');
        }
        // Step 3: Upload to cloud
        $uploadedToCloud = upload_to_cloud($tempBackupZipFile);
        if ($uploadedToCloud) {
            delete_transient('simply_backitup_temp_zip_file');
            wp_send_json_success([
                'message' => 'Backup uploaded to cloud',
                'progress' => 100,
                'backupTime' => get_option('simply_backitup_last_backup')
            ]);
        } else {
            throw new Exception('Cloud upload failed');
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

function export_database()
{
    // Implement your database export logic here
    // Return true on success, false on failure

    // Simulate running task for 5 seconds
    sleep(5);

    return true;
}

function upload_to_cloud($file)
{
    // Implement your cloud upload logic here
    // Return true on success, false on failure

    // Simulate running task for 5 seconds
    sleep(5);

    update_option('simply_backitup_last_backup', date('Y-m-d H:i:s'));

    return true;
}
