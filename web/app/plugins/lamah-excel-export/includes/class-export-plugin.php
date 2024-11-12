<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Export_Plugin {
    private static $instance = null;

    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_dependencies() {
        require_once EXPORT_PLUGIN_PATH . 'includes/class-export-handler.php';
    }

    private function define_hooks() {
        // Activation hook to delete options
        register_activation_hook(__FILE__, [$this, 'delete_export_options']);
        
        // Enqueue JavaScript file and localize script for AJAX
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX actions for logged-in users
        add_action('wp_ajax_start_order_export', ['Export_Handler', 'start_order_export']);
        add_action('wp_ajax_download_export', ['Export_Handler', 'download_export']);
        add_action('wp_ajax_delete_options', [$this, 'delete_export_options']);

        // Redirect for unauthorized AJAX requests
        add_action('wp_ajax_nopriv_start_order_export', [$this, 'handle_unauthorized_request']);

        // WooCommerce Orders page button and button position setup
        add_action('woocommerce_page_wc-orders', [$this, 'render_export_button']);
    }

    public function enqueue_scripts() {
        wp_register_script('export-plugin-script', EXPORT_PLUGIN_URL . 'js/script.js', ['jquery'], null, true);
        wp_enqueue_script('export-plugin-script');
        
        wp_localize_script('export-plugin-script', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('my-ajax-request-nonce')
        ]);
    }

    public function handle_unauthorized_request() {
        wp_die(__('You must log in to access this feature.', 'lamah-plugin'));
    }

    public function delete_export_options() {
        delete_option('lamah-export-status');
        delete_option('lamah-export-path');
    }

    public function render_export_button() {
        $exportState = get_option('lamah-export-status', 'nostate');

        echo '<div id="custom-export-button-wrapper" style="display: none;">';

        // Render appropriate button based on export status
        switch ($exportState) {
            case 'nostate':
                $this->render_start_export_button();
                break;
            case 'loading':
                $this->render_loading_text();
                break;
            case 'complete':
                $this->render_download_button();
                break;
            default:
                update_option('lamah-export-status', 'nostate');
                $this->render_start_export_button();
                break;
        }

        echo '</div>';
    }

    private function render_start_export_button() {
        echo '<button id="l-bt-1" style="height:32px; color: #ffffff; background: #2271b1; border-color: #2271b1;" class="button">' . __('Start Export', 'lamah-plugin') . '</button>';
    }

    private function render_download_button() {
        echo '<button id="l-bt-2" style="height:32px; color: #ffffff; background: #75B214; border-color: #75B214;" class="button">' . __('Download Export', 'lamah-plugin') . '</button>';
    }

    private function render_loading_text() {
        echo '<p id="l-bt-3" style="line-height: 2.15384615; margin: 0;">' . __('Exporting...', 'lamah-plugin') . '</p>';
    }
}

// Initialize the plugin
Export_Plugin::get_instance();
