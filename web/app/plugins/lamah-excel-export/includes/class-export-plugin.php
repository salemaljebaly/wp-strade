<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export_Plugin
{
    private static $instance = null;

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function includes()
    {
        require_once EXPORT_PLUGIN_PATH . 'includes/class-export-handler.php';
    }

    private function init_hooks()
    {
        register_activation_hook(__FILE__, [$this, 'delete_options']);
        add_action('admin_enqueue_scripts', [$this, 'load_js_file']);
        add_action('wp_ajax_start_order_export', ['Export_Handler', 'start_order_export']);
        add_action('wp_ajax_nopriv_start_order_export', [$this, 'must_login']);
        add_action('wp_ajax_download_export', ['Export_Handler', 'download_export']);
        add_action('wp_ajax_delete_options', [$this, 'delete_options']);
        add_action('wp_ajax_display_buttons', [$this, 'display_buttons']);
        add_action('manage_posts_extra_tablenav', [$this, 'display_buttons']);
    }

    public function load_js_file()
    {
        wp_register_script('my-ajax-script', plugins_url('/js/script.js', __FILE__), ['jquery']);
        wp_enqueue_script('jquery');
        wp_enqueue_script('my-ajax-script');
        wp_localize_script('my-ajax-script', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('my-ajax-request-nonce')
        ]);
    }

    public function must_login()
    {
        echo '<br>You must log in';
        die();
    }

    public function delete_options()
    {
        delete_option('lamah-export-status');
        delete_option('lamah-export-path');
    }

    public function display_buttons()
    {

        $exportState = get_option('lamah-export-status');

        switch ($exportState) {
            case 'nostate':
                $this->export_button();
                break;

            case 'loading':
                $this->loading_text();
                break;

            case 'complete':
                $this->download_button();
                break;
            
            default:
                update_option('lamah-export-status', 'nostate');
                $this->export_button();
                break;
        }
    }

    private function export_button()
    {
        global $typenow;
        if ($typenow === 'shop_order') {
            echo '<div class="alignleft actions custom">
                <button id="l-bt-1" style="height:32px; color: #ffffff;background: #2271b1; border-color: #2271b1;" class="button">Start Export</button>
            </div>';
        }
    }

    private function download_button()
    {
        global $typenow;
        if ($typenow === 'shop_order') {
            echo '<div class="alignleft actions custom">
                <button id="l-bt-2" style="height:32px; color: #ffffff;background: #75B214; border-color: #75B214;" class="button">Download Export</button>
            </div>';
        }
    }

    private function loading_text()
    {
        global $typenow;
        if ($typenow === 'shop_order') {
            echo '<div class="alignleft custom">
                <p id="l-bt-3" style="line-height: 2.15384615; margin: 0;">Exporting...</p>
            </div>';
        }
    }
}

