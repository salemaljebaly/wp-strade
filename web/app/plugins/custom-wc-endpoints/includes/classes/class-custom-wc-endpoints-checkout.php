<?php
/**
 * Custom WC Endpoints Checkout
 */
class Custom_WC_Endpoints_Checkout extends Custom_WC_Endpoints  {
    
    /**
     * Handle custom checkout
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_custom_checkout( WP_REST_Request $request ) {
        $request_data = $request->get_json_params();

        // Validate billing address fields
        $billing = $request_data['billing'];
        $required_fields = array( 'first_name', 'last_name', 'city', 'phone' );
        $missing_fields = array_filter( $required_fields, function ( $field ) use ( $billing ) {
            return empty( $billing[ $field ] );
        } );

        if ( ! empty( $missing_fields ) ) {
            $error_message = 'The following billing fields are required: ' . implode( ', ', $missing_fields );
            return new WP_Error( 'missing_fields', $error_message, array( 'status' => 400 ) );
        }

        wc_load_cart();
        $cart = WC()->cart->get_cart();

        if (empty($cart)) {
            return new WP_Error('no_items', 'No items in the cart', array('status' => 400));
        }

        $response = $this->process_checkout( $cart, $request_data );
        $this->clear_cart();

        return new WP_REST_Response( $response );
    }

    /**
     * Clear cart
     *
     * @param string $token
     * @return array|WP_Error
     */
    public function clear_cart() {
        wc_load_cart();
        WC()->cart->empty_cart();
    }

    /**
     * Process checkout
     *
     * @param array $cart_data
     * @param array $request_data
     * @return array
     */
    public function process_checkout( $cart, $request_data ) {
        // Create a new order
        $order = wc_create_order();

        // Add customer billing details
        $billing_address = array(
            'first_name' => $request_data['billing']['first_name'],
            'last_name'  => $request_data['billing']['last_name'],
            'email'      => '',
            'phone'      => $request_data['billing']['phone'],
            'address_1'  => '',
            'address_2'  => '',
            'city'       => $request_data['billing']['city'],
            'state'      => '',
            'postcode'   => '',
            'country'    => 'LY',
        );

        // Set billing address
        $order->set_address($billing_address, 'billing');

        // Set shipping address same as billing
        $order->set_address($billing_address, 'shipping');

        // Set customer note
        $customer_note = isset( $request_data['customer_note'] ) ? $request_data['customer_note'] : '';
        $order->set_customer_note( $customer_note );

        // Set payment method and payment method title
        $order->set_payment_method( 'cod' );
        $order->set_payment_method_title( 'دفع عند الإستلام' );

        // Add order items
        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $order->add_product($product, $quantity);

            // Collect cart item data
            $cart_items[] = array(
                'product_id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'quantity' => $quantity,
                'total' => $cart_item['line_total'],
            );
        }

        $order->calculate_totals();
        $order->update_status( 'processing'); // change status to pending if you want disable email notification

        $response = array(
            'order_id' => $order->get_id(),
            'order_date' => $order->get_date_created()->date('Y-m-d'),
            'billing_address' => array(
                'first_name' => $billing_address['first_name'],
                'last_name' => $billing_address['last_name'],
                'phone' => $billing_address['phone'],
                'city' => $billing_address['city'],
                'email' => wp_get_current_user()->user_email
            ),
            'order_items' => $cart_items,
            'order_total' => array(
                "price" => $order->get_total(),
                'quantity' => $order->get_item_count(),
            )
        );

        return $response;
    }
}