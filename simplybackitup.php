<?php
/*
Plugin Name: Simply BackItUp
Plugin URI: http://yourwebsite.com/
Description: A plugin to backup your WordPress site.
Version: 1.0
Author: Anthony M. Darter
Author URI: http://yourwebsite.com/
License: MIT
Text Domain: simply-backitup
*/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use AMDarter\SimplyBackItUp\Controllers\Backup;
use AMDarter\SimplyBackItUp\Controllers\Settings;

require_once __DIR__ . '/vendor/autoload.php';

define('SIMPLY_BACKITUP_ENV_MODE', 'DEVELOPMENT');
define('SIMPLY_BACKITUP_MIN_PHP_VERSION', '8.0');

class SimplyBackItUp
{
    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'onActivation'));
        $this->addActions();
    }

    public function ajaxMiddleware($next): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'simply-backitup'));
        }
        check_ajax_referer('simply_backitup_nonce', 'nonce');
        if (! is_callable($next)) {
            wp_die(esc_html__('Invalid action.', 'simply-backitup'));
        }
        call_user_func($next);
    }

    protected function addActions(): void
    {
        add_action(
            'wp_ajax_simply_backitup_all_history',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'allHistory'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_clear_history',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'clearHistory'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_save_settings',
            function () {
                $this->ajaxMiddleware(array(Settings::class, 'save'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_all_settings',
            function () {
                $this->ajaxMiddleware(array(Settings::class, 'index'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step0',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'step0'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step1',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'step1'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step2',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'step2'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_step3',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'step3'));
            }
        );
        add_action(
            'wp_ajax_simply_backitup_download_backup_zip',
            function () {
                $this->ajaxMiddleware(array(Backup::class, 'downloadBackupZip'));
            }
        );
        add_action(
            'admin_menu',
            array($this, 'addAdminMenu')
        );
        add_action(
            'admin_enqueue_scripts',
            array($this, 'enqueueAdminScripts')
        );
    }

    public $transientKey = 'simply_backitup_temp_zip_file';

    public function onActivation(): void
    {
        load_plugin_textdomain(
            'simply-backitup',
            false,
            plugin_basename(__DIR__) . '/languages/'
        );

        if (version_compare(PHP_VERSION, SIMPLY_BACKITUP_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__(
                    'Simply BackItUp requires PHP 8.0 or higher. Please contact your hosting provider to upgrade your PHP version.',
                    'simply-backitup'
                ),
                esc_html__('Plugin Activation Error', 'simply-backitup'),
                array('back_link' => true)
            );
        }

        if (! extension_loaded('zip')) {
            deactivate_plugins(plugin_basename(__FILE__));
            $error_message = esc_html__(
                'Simply BackItUp could not be activated because it requires the ZipArchive extension, which is not loaded. Please enable the ZipArchive extension in your PHP configuration and try again.',
                'simply-backitup'
            );
            wp_die(
                $error_message,
                esc_html__(
                    'Plugin Activation Error',
                    'simply-backitup'
                ),
                array('back_link' => true)
            );
        }

        if (!extension_loaded('ftp')) {
            deactivate_plugins(plugin_basename(__FILE__));
            $error_message = esc_html__(
                'Simply BackItUp could not be activated because it requires the FTP extension, which is not loaded. Please enable the FTP extension in your PHP configuration and try again.',
                'simply-backitup'
            );
            wp_die(
                $error_message,
                esc_html__('Plugin Activation Error', 'simply-backitup'),
                array('back_link' => true)
            );
        }
    }

    public function addAdminMenu(): void
    {
        add_menu_page(
            esc_html__('BackItUp', 'simply-backitup'),
            esc_html__('BackItUp', 'simply-backitup'),
            'manage_options',
            'simply-backitup',
            function () {
                echo '<div id="simply-backitup-root"></div>';
            }
        );
    }

    public function enqueueAdminScripts($hookSuffix): void
    {
        if ($hookSuffix === 'toplevel_page_simply-backitup') {
            $jsFiles = glob(plugin_dir_path(__FILE__) . 'src/Admin/dist/*.js');
            if (! empty($jsFiles)) {
                $wp_enqueue_module_function = 'wp_enqueue_module';
                if (function_exists('wp_enqueue_module')) {
                    $wp_enqueue_module_function(
                        'simply-backitup-admin-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        array(),
                        null
                    );
                } else {
                    wp_enqueue_script(
                        'simply-backitup-admin-script',
                        plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($jsFiles[0]),
                        array(),
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
            if (! empty($cssFiles)) {
                wp_enqueue_style(
                    'simply-backitup-admin-style',
                    plugin_dir_url(__FILE__) . 'src/Admin/dist/' . basename($cssFiles[0]),
                    array(),
                    null
                );
            }
            wp_localize_script('simply-backitup-admin-script', 'SimplyBackItUp', array(
                'ajaxUrl'   => esc_url(admin_url('admin-ajax.php')),
                'nonce'     => wp_create_nonce('simply_backitup_nonce'),
                'pluginUrl' => esc_url(plugin_dir_url(__FILE__)),
            ));
        }
    }
}

new SimplyBackItUp();
