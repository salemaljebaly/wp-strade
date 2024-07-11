<?php
/**
 * Plugin Name: Custom WC Endpoints
 * Plugin URI: https://lamah.com
 * Description: Registers custom WooCommerce endpoints for products, categories, and checkout.
 * Version: 1.0.0
 * Author: Lamah WebDev
 * Author URI: https://lamah.com
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints-products.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints-categories.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints-checkout.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints-user.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-custom-wc-endpoints-register.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/custom-hooks.php';




// Initialize the plugin
$custom_wc_endpoints = new Custom_WC_Endpoints();