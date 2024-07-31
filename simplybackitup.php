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

add_action('admin_enqueue_scripts', function($hook_suffix) {
    if ($hook_suffix === 'toplevel_page_simply-backitup') {
        $settings = [
            'frequency' => get_option('simply_backitup_frequency', 'daily'),
            'time' => get_option('simply_backitup_time', '03:00'),
            'email' => get_option('simply_backitup_email', '')
        ];
        wp_enqueue_style('simply-backitup-style', plugin_dir_url(__FILE__) . 'src/Admin/css/backup-settings-admin-page.css', [], '1.0');
        wp_enqueue_script('simply-backitup-script', plugin_dir_url(__FILE__) . 'src/Admin/js/backup-settings-admin-page.js', [], '1.0', true);
        wp_localize_script('simply-backitup-script', 'SimplyBackItUp', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simply_backitup_nonce'),
            'settings' => $settings
        ]);
    }
});

add_action('wp_ajax_simply_backitup_save_settings', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }

    check_ajax_referer('simply_backitup_nonce', 'nonce');

    $frequency = sanitize_text_field($_POST['frequency']);
    $time = sanitize_text_field($_POST['time']);
    $email = sanitize_email($_POST['email']);

    if (!is_email($email)) {
        wp_send_json_error('Invalid email address.');
        return;
    }

    if (!in_array($frequency, ['daily', 'weekly', 'monthly'])) {
        wp_send_json_error('Invalid frequency.');
        return;
    }

    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        wp_send_json_error('Invalid time.');
        return;
    }

    update_option('simply_backitup_frequency', $frequency);
    update_option('simply_backitup_time', $time);
    update_option('simply_backitup_email', $email);

    wp_send_json_success();
});

add_action('wp_ajax_simply_backitup_step1', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    check_ajax_referer('simply_backitup_nonce', 'nonce');
    try {
        // Step 1: Zip up files
        $tempZipService = new TempZip();
        $tempBackupZipFile = $tempZipService->tempDir() . $tempZipService->generateFilename();
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
        $uploadedToCloud = upload_to_cloud($tempBackupZipFile); // Implement this function
        if ($uploadedToCloud) {
            delete_transient('simply_backitup_temp_zip_file');
            wp_send_json_success(['message' => 'Backup uploaded to cloud', 'progress' => 100]);
        } else {
            throw new Exception('Cloud upload failed');
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

function export_database() {
    // Implement your database export logic here
    // Return true on success, false on failure

    // Simulate running task for 5 seconds
    sleep(5);

    return true;
}

function upload_to_cloud($file) {
    // Implement your cloud upload logic here
    // Return true on success, false on failure

    // Simulate running task for 5 seconds
    sleep(5);

    return true;
}
