<?php
// Redirect all non-admin users to the specified domain
if (!is_admin()) {
    wp_redirect('https://strade.ly');
    exit;
}

function mytheme_add_woocommerce_support() {
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'mytheme_add_woocommerce_support' );

?>
