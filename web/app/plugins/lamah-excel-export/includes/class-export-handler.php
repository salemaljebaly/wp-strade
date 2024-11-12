<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export_Handler
{
    const EXPORT_FILE_PREFIX = 'orders-';
    const EXPORT_STATUS_OPTION = 'lamah-export-status';
    const EXPORT_PATH_OPTION = 'lamah-export-path';

    public static function start_order_export()
    {
        // Verify AJAX nonce
        if (!check_ajax_referer('my-ajax-request-nonce', 'security', false)) {
            wp_send_json(['status' => false, 'message' => 'Nonce verification failed.']);
            return;
        }

        // Update export status and delete previous files
        update_option(self::EXPORT_STATUS_OPTION, 'loading');
        self::delete_xlsx_files();

        // Fetch products and orders
        $products = wc_get_products(['limit' => -1, 'status' => 'publish', 'return' => 'ids']);
        $order_ids = wc_get_orders(['limit' => -1, 'status' => ['processing', 'completed'], 'return' => 'ids']);

        // Define header columns
        $table_columns = ["Name", "Categories", "ImageURL", "ImageType", "Pice", "SKU", "Regular Price", "Single Price", "Total Sales"];
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            $col = $order->get_formatted_billing_full_name();
            $table_columns[] = $col;
        }
        // Initialize spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header row
        foreach ($table_columns as $columnIndex => $field) {
            $sheet->setCellValue([$columnIndex + 1, 1], $field);
        }

        $excel_row = 2;
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);

            // Gather product data
            $data = [
                $product->get_name(),
                strip_tags(wc_get_product_category_list($product_id)),
                get_post_meta($product_id, 'imageurl', true),
                get_post_meta($product_id, 'imagetype', true),
                get_post_meta($product_id, 'pice', true),
                $product->get_sku(),
                $product->get_regular_price(),
                get_post_meta($product_id, 'singleprice', true),
                self::get_total_sales($order_ids, $product_id),
            ];

            // Fill in product data
            foreach ($data as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $excel_row], $value);
            }

            // Add quantity data for each order
            $col_index = 10;
            foreach ($order_ids as $order_id) {
                $total_sold = self::get_total_sold($order_id, $product_id);
                $sheet->setCellValue([$col_index, $excel_row], $total_sold);
                $col_index++;
            }

            $excel_row++;
        }

        // Save spreadsheet to file
        $uploads = wp_upload_dir();
        $fileName = self::EXPORT_FILE_PREFIX . date('YmdHis') . '.xlsx';
        $filePath = $uploads['path'] . '/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Update option with the file path
        $download_url = $uploads['url'] . '/' . $fileName;
        update_option(self::EXPORT_PATH_OPTION, $download_url);
        update_option(self::EXPORT_STATUS_OPTION, 'complete');

        wp_send_json(['status' => true, 'downloadPath' => $download_url]);
    }

    private static function get_total_sales($order_ids, $product_id)
    {
        $total_sales = 0;
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $product_id) {
                    $total_sales += $item->get_quantity();
                }
            }
        }
        return $total_sales === 0 ? '' : (string)$total_sales;
    }

    private static function get_total_sold($order_id, $product_id)
    {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                return $item->get_quantity();
            }
        }
        return '';
    }

    public static function download_export()
    {
        if (!check_ajax_referer('my-ajax-request-nonce', 'security', false)) {
            wp_send_json(['status' => false, 'message' => 'Nonce verification failed.']);
            return;
        }
        wp_send_json(['downloadPath' => get_option(self::EXPORT_PATH_OPTION)]);
    }

    public static function delete_xlsx_files()
    {
        $uploads = wp_upload_dir();
        $files = glob($uploads['path'] . '/' . self::EXPORT_FILE_PREFIX . '*.xlsx');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
