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
        wp_enqueue_script('simply-backitup-script', plugin_dir_url(__FILE__) . 'src/Admin/js/backup-settings-admin-page.js', [], '1.0', true);
        wp_localize_script('simply-backitup-script', 'SimplyBackItUp', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simply_backitup_nonce')
        ]);
    }
});

add_action('wp_ajax_simply_backitup_backup_site', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    check_ajax_referer('simply_backitup_nonce', 'nonce');
    try {
        $tempZipService = new TempZip();
        $tempBackupZipFile = $tempZipService->tempDir() . $tempZipService->generateFilename();
        $tempZipService->zipDir(ABSPATH, $tempBackupZipFile);
        /**
         * @todo: Post the ZIP to a cloud storage service or download it directly.
         */
        $tempZipService->cleanup();
        wp_send_json_success();
    } catch (\Exception $e) {
        error_log($e->getMessage());
        wp_send_json_error();
    }
});
