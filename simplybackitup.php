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

use Microsoft\Graph\Model\Call;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use \AMDarter\SimplyBackItUp\Controllers\Backup;
use \AMDarter\SimplyBackItUp\Controllers\Settings;

require_once __DIR__ . '/vendor/autoload.php';

define('SIMPLY_BACKITUP_ENV_MODE', 'DEVELOPMENT');

class SimplyBackItUp
{
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'onActivation']);
        $this->addActions();
    }

    public function ajaxMiddleware($next): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        if (!is_callable($next)) {
            wp_die('Invalid action');
        }
        call_user_func($next);
    }

    protected function addActions(): void
    {
        add_action(
            'wp_ajax_simply_backitup_all_history',
            function () {
                $this->ajaxMiddleware([Backup::class, 'allHistory']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_save_settings',
            function () {
                $this->ajaxMiddleware([Settings::class, 'save']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_all_settings',
            function () {
                $this->ajaxMiddleware([Settings::class, 'index']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step1',
            function () {
                $this->ajaxMiddleware([Backup::class, 'step1']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step2',
            function () {
                $this->ajaxMiddleware([Backup::class, 'step2']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step3',
            function () {
                $this->ajaxMiddleware([Backup::class, 'step3']);
            }
        );
        add_action(
            'wp_ajax_simply_backitup_download_backup_zip',
            function () {
                $this->ajaxMiddleware([Backup::class, 'downloadBackupZip']);
            }
        );
        add_action(
            'admin_menu',
            [$this, 'addAdminMenu']
        );
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueueAdminScripts']
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
            echo '<div id="simply-backitup-root"></div>';
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
                        'simply-backitup-admin-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        [],
                        null
                    );
                } else {
                    wp_enqueue_script(
                        'simply-backitup-admin-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        [],
                        null,
                        true
                    );
                    add_filter('script_loader_tag', function ($tag, $handle) {
                        if ($handle === 'simply-backitup-admin-script') {
                            return str_replace(' src=', ' type="module" src=', $tag);
                        }
                        return $tag;
                    }, 10, 2);
                }
            }
            $cssFiles = glob(plugin_dir_path(__FILE__) . 'src/Admin/dist/*.css');
            if (!empty($cssFiles)) {
                wp_enqueue_style(
                    'simply-backitup-admin-style',
                    plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($cssFiles[0]),
                    [],
                    null
                );
            }
            wp_localize_script('simply-backitup-admin-script', 'SimplyBackItUp', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('simply_backitup_nonce'),
                'pluginUrl' => plugin_dir_url(__FILE__)
            ]);
        }
    }
}

new SimplyBackItUp();
