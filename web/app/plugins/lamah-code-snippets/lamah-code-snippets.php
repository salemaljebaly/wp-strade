<?php

/*
* Plugin Name: Code Snippets
* Description: A set of code snippets added manually in one plugin
* Version:     1.0
* Author:      Lamah Dev
* Author URI:  https://lamah.com
*/

/*****************************************************/

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


// Disable WordPress Automatically Generated Images

function shapeSpace_disable_image_sizes($sizes)
{
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['large']);
    unset($sizes['medium_large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);
    unset($sizes['woocommerce_thumbnail']);
    unset($sizes['woocommerce_single']);
    unset($sizes['woocommerce_gallery_thumbnail']);
    return $sizes;
}
add_action('intermediate_image_sizes_advanced', 'shapeSpace_disable_image_sizes');


/**************************************************** */


// Total Cartons

function display_custom_total()
{
    // Get (sub)total
    $total_count = WC()->cart->get_cart_contents_count();

    // The Output
    echo ' <tr class="cart-total-to-pay">
        <th>' . __('إجمالي الكراتين', 'woocommerce') . '</th>
        <td data-title="إجمالي الكراتين">' . $total_count . ' كرتونة</td>
    </tr>';
}
add_action('woocommerce_cart_totals_before_order_total', 'display_custom_total', 20);
add_action('woocommerce_review_order_before_order_total', 'display_custom_total', 20);


/**************************************************** */

// Redirect suspended user

function redirect_suspended_user()
{
    $current_user = wp_get_current_user();
    $url = 'https://strade.ly/suspend.html';
    if ($current_user->exists()) {
        if (current_user_can('suspended')) {
            wp_redirect($url);
            exit();
        }
    }
}
add_action('template_redirect', 'redirect_suspended_user');

/**************************************************** */

// Show meta data

/**
 * Display custom field on single product page
 */
function display_custom_field()
{
    global $product;
    $custom_field = get_post_meta($product->get_id(), 'singleprice', true);
    $piece = get_post_meta($product->get_id(), 'pice', true);

    echo '<br><p class="metaData">' . esc_html__(' سعر القطعة :', 'woocommerce') . ' ' . esc_html($custom_field) . ' د.ل</p>';
    echo '<p class="metaData">' . esc_html__(' العبوة :', 'woocommerce') . ' ' . esc_html($piece) . ' قطعة</p>';

    //if ( ! empty( $custom_field ) ) {

    //}
}
add_action('woocommerce_before_add_to_cart_form', 'display_custom_field', 20);


/**************************************************** */

// modifications to the media before uploading

function modifications_before_upload($file)
{
    // Fix img metadata code
    $allowed_extensions = array('jpg', 'jpeg', 'png');
    $file_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file['name'] = $file_name . '.' . $file_extension;
    
    if (in_array($file_extension, $allowed_extensions)) {
        $img_path = $file['tmp_name']; // Get temporary file path
        $image = imagecreatefromjpeg($img_path);

        // Save the modified image, overwriting the original temporary file
        imagejpeg($image, $img_path);
        imagedestroy($image);
    }
    
    // Disable uploading media with the same name
    $filename = sanitize_file_name($file['name']);
    $uploads_dir = wp_upload_dir();
    $search_filename = sprintf('%s/%s', $uploads_dir['path'], $filename);

    if (file_exists($search_filename)) {
        $file['error'] = 'الصورة موجودة بنفس الاسم مسبقاً!';
    }

    return $file;
}

add_filter('wp_handle_upload_prefilter', 'modifications_before_upload');

/************************************************************ */


// Add a custom field (in an order) to the emails

add_action('woocommerce_email_order_details', 'remove_order_details', 1, 4);
add_action('woocommerce_email_order_details', 'action_woocommerce_email_order_details', 10, 4);


// define the woocommerce_email_order_details callback

function get_total_number_of_items_in_order($order_id)
{
    $order = wc_get_order($order_id);
    $total_quantity = 0;
    foreach ($order->get_items() as $item_id => $item) {
        $quantity = $item->get_quantity();
        $total_quantity += $quantity;
    }
    return $total_quantity;
}

function action_woocommerce_email_order_details($order, $sent_to_admin, $plain_text, $email)
{
    $text_align = is_rtl() ? 'right' : 'left';

    do_action('woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email); ?>

    <h2>
        <?php
        if ($sent_to_admin) {
            $before = '<a class="link" href="' . esc_url($order->get_edit_order_url()) . '">';
            $after  = '</a>';
        } else {
            $before = '';
            $after  = '';
        }
        /* translators: %s: Order ID. */
        echo wp_kses_post($before . sprintf(__('[Order #%s]', 'woocommerce') . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format('c'), wc_format_datetime($order->get_date_created())));
        ?>
    </h2>

    <div style="margin-bottom: 40px;">
        <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
            <thead>
                <tr>
                    <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                    <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                    <th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Price', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    $order,
                    array(
                        'show_sku'      => $sent_to_admin,
                        'show_image'    => false,
                        'image_size'    => array(32, 32),
                        'plain_text'    => $plain_text,
                        'sent_to_admin' => $sent_to_admin,
                    )
                );
                ?>
            </tbody>
            <tfoot>
                <?php
                $item_totals = $order->get_order_item_totals();

                if ($item_totals) {
                ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr($text_align); ?>; <?php echo 'border-top-width: 4px;'; ?>">
                            <?php echo wp_kses_post($item_totals['order_total']['label']); ?></th>
                        <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>; <?php echo 'border-top-width: 4px;'; ?>">
                            <?php echo wp_kses_post($item_totals['order_total']['value']); ?></td>
                    </tr>

                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr($text_align); ?>;">
                            <?php echo 'إجمالي الكراتين:'; ?></th>
                        <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>;">
                            <?php echo get_total_number_of_items_in_order($order->get_id()) . ' كرتونة'; ?></td>
                    </tr>
                <?php
                }


                if ($order->get_customer_note()) {
                ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Note:', 'woocommerce'); ?></th>
                        <td class="td" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php echo wp_kses_post(nl2br(wptexturize($order->get_customer_note()))); ?></td>
                    </tr>
                <?php
                }
                ?>
            </tfoot>
        </table>
    </div>
<?php
}

function remove_order_details()
{
    $mailer = WC()->mailer(); // get the instance of the WC_Emails class
    remove_action('woocommerce_email_order_details', array($mailer, 'order_details'));
}


/*********************************************************/


// Add Custom Column To Orders Table

function show_total_cartons_column($columns)
{

    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key ==  'order_status') {
            // Inserting after "Status" column
            $reordered_columns['total_orders_cartons'] = __('عدد الكراتين', 'theme_domain');
        }
    }
    return $reordered_columns;
}


function populate_total_cartons_column($column)
{
    global $post;
    if ('total_orders_cartons' === $column) {
        $order = wc_get_order($post->ID);
        echo get_total_number_of_items_in_order($order->get_id());
    }
}

add_filter('manage_edit-shop_order_columns', 'show_total_cartons_column');
add_action('manage_shop_order_posts_custom_column', 'populate_total_cartons_column');

/********************************************************* */
