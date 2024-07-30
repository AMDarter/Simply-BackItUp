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

require_once __DIR__ . '/vendor/autoload.php';

use AMDarter\BackupEngine;

$backupEngine = new BackupEngine();

add_action('init', [$backupEngine, 'init']);