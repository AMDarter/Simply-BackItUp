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

use AMDarter\SimplyBackItUp\Service\TempZip;

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

function amdarter_backup_admin_page()
{
?>
    <div class="wrap">
        <h1>Backup Settings</h1>
        <p>
            <label for="backup-frequency">Backup Frequency</label>
            <select id="backup-frequency">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </p>
        <p>
            <label for="backup-time">Backup Time</label>
            <input type="time" id="backup-time" value="03:00">
        </p>
        <p>
            <label for="backup-email">Backup Email</label>
            <input type="email" id="backup-email" placeholder="Enter email address">
        </p>
        <button id="save-settings" class="button button-primary">Save Settings</button>
        <button id="backup-site" class="button button-primary">Backup Now</button>
    </div>
    <script>
        document.getElementById('backup-site').addEventListener('click', function() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    alert('Backup completed successfully.');
                    location.reload();
                } else {
                    alert('Backup failed.');
                }
            };
            xhr.send('action=amdarter_backup_site');
        });
    </script>
<?php
}

// Show the admin page for settings
add_action('admin_menu', 'amdarter_backup_admin_menu');

function amdarter_backup_admin_menu()
{
    add_menu_page('Backup', 'Backup', 'manage_options', 'amdarter-backup', 'amdarter_backup_admin_page');
}

// Ajax action to backup the site.
add_action('wp_ajax_amdarter_backup_site', function () {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
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
