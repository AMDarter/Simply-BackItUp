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

require_once __DIR__ . '/vendor/autoload.php';

define('SIMPLY_BACKITUP_ENV_MODE', 'DEVELOPMENT');

class SimplyBackItUp
{
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'onActivation']);
        $this->addActions();
    }

    protected function addActions(): void
    {
        add_action(
            'admin_menu',
            [$this, 'addAdminMenu']
        );
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAdminScripts']
        );
        add_action(
            'wp_ajax_simply_backitup_save_settings',
            [\AMDarter\SimplyBackItUp\Controllers\SaveSettings::class, 'save']
        );
        add_action(
            'wp_ajax_simply_backitup_step1',
            [\AMDarter\SimplyBackItUp\Controllers\Backup::class, 'step1']
        );
        add_action(
            'wp_ajax_simply_backitup_step2',
            [\AMDarter\SimplyBackItUp\Controllers\Backup::class, 'step2']
        );
        add_action(
            'wp_ajax_simply_backitup_step3',
            [\AMDarter\SimplyBackItUp\Controllers\Backup::class, 'step3']
        );
        add_action(
            'wp_ajax_simply_backitup_download_backup_zip',
            [\AMDarter\SimplyBackItUp\Controllers\Backup::class, 'downloadBackupZip']
        );
    }

    public $transientKey = 'simply_backitup_temp_zip_file';

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
                'settings' => [
                    'backupFrequency' => get_option('simply_backitup_frequency', 'daily'),
                    'backupTime' => get_option('simply_backitup_time', '03:00'),
                    'backupEmail' => get_option('simply_backitup_email', ''),
                    'backupStorageLocation' => get_option('simply_backitup_backup_storage_location', ''),
                    'lastBackupTime' => get_option('simply_backitup_last_backup', null),
                    'backupStorageCredentials' => get_option('simply_backitup_backup_storage_credentials', [])
                ]
            ]);
        }
    }
}

new SimplyBackItUp();
