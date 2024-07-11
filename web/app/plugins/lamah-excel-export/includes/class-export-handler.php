<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Export_Handler
{
    public static function start_order_export()
    {
        update_option('lamah-export-status', 'loading');
        self::delete_xlsx_files();

        global $wpdb;
        $query = "SELECT product_qty, order_id, product_id FROM {$wpdb->prefix}wc_order_product_lookup;";
        $output = $wpdb->get_results($query);

        $product_qtys = [];
        $order_ids = [];
        $product_ids = [];

        foreach ($output as $out) {
            $product_qtys[] = $out->product_qty;
            $order_ids[] = $out->order_id;
            $product_ids[] = $out->product_id;
        }

        $products = wc_get_products(['limit' => -1, 'status' => 'publish', 'return' => 'ids']);
        $orders = wc_get_orders(['limit' => -1, 'status' => ['processing', 'completed'], 'return' => 'ids']);

        $table_columns = ["Name", "Categories", "ImageURL", "ImageType", "Pice", "SKU", "Regular Price", "Single Price", "Total Sales"];
        foreach ($orders as $order) {
            $order = wc_get_order($order);
            $col = $order->get_formatted_billing_full_name();
            $table_columns[] = $col;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $column = 1;
        foreach ($table_columns as $field) {
            $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . 1;
            $sheet->getCell($coordinate)->setValue($field);
            $column++;
        }

        $excel_row = 2;
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            $data = [
                $product->get_name(),
                strip_tags(wc_get_product_category_list($product_id)),
                get_post_meta($product_id, 'imageurl', true),
                get_post_meta($product_id, 'imagetype', true),
                get_post_meta($product_id, 'pice', true),
                $product->get_sku(),
                $product->get_regular_price(),
                get_post_meta($product_id, 'singleprice', true),
                self::get_total_sales($orders, $product_id)
            ];

            $col_index = 10;
            foreach ($orders as $order_id) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index) . $excel_row;
                $order = wc_get_order($order_id);
                $total_sold = self::get_total_sold($order_ids, $product_ids, $product_qtys, $order->get_id(), $product_id);
                $sheet->getCell($coordinate)->setValue($total_sold);
                $col_index++;
            }

            $row_index = 1;
            foreach ($data as $value) {
                $sheet->getCellByColumnAndRow($row_index++, $excel_row)->setValue($value);
            }
            $excel_row++;
        }

        $uploads = wp_upload_dir();
        $fileName = 'orders-' . date('YmdHis') . '.xlsx';
        $filePath = $uploads['path'] . '/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $download_url = $uploads['url'] . '/' . $fileName;
        update_option('lamah-export-path', $download_url);
        update_option('lamah-export-status', 'complete');

        wp_send_json(['status' => true, 'downloadPath' => $download_url]);
    }

    private static function get_total_sales($orders, $product_id)
    {
        $total_sales = 0;
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                if ($item['product_id'] == $product_id) {
                    $total_sales += $item['qty'];
                }
            }
        }
        return (string)$total_sales === '0' ? '' : (string)$total_sales;
    }

    private static function get_total_sold($order_ids, $product_ids, $product_qtys, $order_id, $product_id)
    {
        for ($i = 0; $i < count($product_qtys); $i++) {
            if ($order_ids[$i] == $order_id && $product_ids[$i] == $product_id) {
                return $product_qtys[$i];
            }
        }
        return '';
    }

    public static function download_export()
    {
        wp_send_json(['downloadPath' => get_option('lamah-export-path')]);
    }

    public static function delete_xlsx_files()
    {
        $uploads = wp_upload_dir();
        $files = glob($uploads['path'] . '/orders*.xlsx');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}

