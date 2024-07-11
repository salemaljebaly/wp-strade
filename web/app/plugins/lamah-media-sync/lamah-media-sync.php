<?php

/**
 * @package MediaSyncPlugin
 */

/*
Plugin Name: Lamah Products Media Sync
Description: Synchronize images with products by product name
Version:     1.0
Author:      Lamah Dev
Author URI:  http://lamah.com
License: GPLv2 or later
Text Domain: firas-plugin
*/

/************************************************** */

// Load js Section


add_action('admin_enqueue_scripts', 'media_sync_scripts');

function media_sync_scripts()
{
    wp_register_script('media-ajax-script', plugins_url('/js/script.js', __FILE__), array('jquery'));
    wp_enqueue_script('jquery');
    wp_enqueue_script('media-ajax-script');

    // Localize the script with the appropriate nonce
    wp_localize_script('media-ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'security' => wp_create_nonce('media-ajax-request-nonce')));
}



/************************************************** */


// Load AJAX Section
// 10, 1 mean only allow admins

function media_must_login()
{
    echo '<br>';
    echo "You must log in";
    die();
}

add_action('wp_ajax_sync_media_products', 'sync_media_products', 10, 1);
add_action('wp_ajax_nopriv_sync_media_products', 'media_must_login');



/************************************************** */



// Another way to update images (not used)

function update_product_images_2($data)
{
    global $wpdb;

    // get product title (name)
    $title = sanitize_text_field($data['title']);

    // get attachment ID for image with same name
    $image_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'attachment'",
            $title
        )
    );

    if ($image_id) {
        //   get product ID  
        $product_id = intval($data['product_id']);

        // update product with image
        $product = wc_get_product($product_id);
        $product->set_image_id($image_id);
        $product->save();
    }
}




function update_product_images($data, $attachment_ids)
{

    // Create an associative array with title as key and ID as value
    $attachment_mapping = array();
    foreach ($attachment_ids as $attachment) {
        $attachment_mapping[$attachment['post_title']] = $attachment['ID'];
    }

    // Get product title (name)
    $title = sanitize_text_field($data['title']);

    // Get attachment ID for image with the same name
    $image_id = isset($attachment_mapping[$title]) ? $attachment_mapping[$title] : 0;


    if ($image_id) {

        // Get product ID
        $product_id = intval($data['product_id']);

        // Update product with the image
        $product = wc_get_product($product_id);
        $product->set_image_id($image_id);
        $product->save();
    }
}


// media sync action

function sync_media_products()
{

    update_option('lamah-media-sync-status', 'loading');

    global $wpdb;


    $attachment_ids = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'attachment'"
        ),
        ARRAY_A
    );

    $products = wc_get_products(array('limit' => -1));

    $batch_size = 100; // Number of products to process in each batch
    $product_count = count($products);
    $batch_count = ceil($product_count / $batch_size);

    for ($i = 0; $i < $batch_count; $i++) {
        $offset = $i * $batch_size;
        $batch_products = array_slice($products, $offset, $batch_size);

        foreach ($batch_products as $product) {
            $data = array(
                'title' => $product->get_name(),
                'product_id' => $product->get_id()
            );

            update_product_images($data, $attachment_ids);
        }
    }

    delete_option('lamah-media-sync-status');
    wp_send_json_success();
}




/************************************************** */


// Check View Buttons Section

function media_display_buttons()
{

    if (get_option('lamah-media-sync-status') === 'loading') {

        add_action('manage_posts_extra_tablenav', 'lamah_loading_media', 20, 1);
    } else {
        add_action('manage_posts_extra_tablenav', 'add_product_images_button', 20, 1);
    }
}

media_display_buttons();






/************************************************** */



function add_product_images_button()
{

    global $typenow;

    if ($typenow == 'product') {
?>
        <div class="alignleft actions custom">
            <button id="media-sync" style="height:32px; color: #ffffff;background: #2271b1; border-color: #2271b1;" class="button"><?php
                                                                                                                                    echo __('مزامنة الصور', 'woocommerce'); ?></button>
        </div>
    <?php
    }
}


function lamah_loading_media()
{

    global $typenow;

    if ($typenow == 'product') {
    ?>
        <div class="alignleft custom">
            <p id="l-bt-3" style="line-height: 2.15384615; margin: 0;">
                <?php
                echo __(' جاري المزامنة ..', 'woocommerce'); ?></p>
        </div>
<?php
    }
}


?>