<?php
/*
Plugin Name: Backup
Plugin URI: http://yourwebsite.com/
Description: A plugin to backup your WordPress site.
Version: 1.0
Author: Anthony M. Darter
Author URI: http://yourwebsite.com/
License: MIT
*/

use AMDarter\ZipManager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

// Register the activation hook.
register_activation_hook(__FILE__, 'amdarter_backup_plugin_activate');

function amdarter_backup_plugin_activate()
{
    if (!extension_loaded('zip')) {
        deactivate_plugins(plugin_basename(__FILE__));
        $error_message = 'The Backup Plugin could not be activated because it requires the ZipArchive extension, which is not loaded. Please enable the ZipArchive extension in your PHP configuration and try again.';
        wp_die($error_message, 'Plugin Activation Error', ['back_link' => true]);
    }
}

add_action('init', function () {

});

// Add action wp fully loaded.
add_action('wp_loaded', function () {
    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    try {
        $zipManager = new ZipManager();
        $tempBackupZipFile = $zipManager->tempBackupDir() . $zipManager->generateTempZipFilename();
        $zipManager->zipDir(ABSPATH, $tempBackupZipFile);
        /**
         * @todo: Post the ZIP to a cloud storage service or download it directly.
         */
        $zipManager->cleanupTempZips();
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
});