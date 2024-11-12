<?php

/**
 * @package ExportExcelPlugin
 */

/*
Plugin Name: Export Orders-Products in Excel Sheet
Description: Enables you to export a list of products as rows and customer orders as columns in a streamlined manner, consolidating the relevant data into a single Excel file.
Version:     2.0
Author:      Lamah Dev
Author URI:  http://lamah.com
License: GPLv2 or later
Text Domain: lamah-plugin
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('EXPORT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EXPORT_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once EXPORT_PLUGIN_PATH . 'includes/class-export-plugin.php';

// Initialize the plugin
Export_Plugin::get_instance();

