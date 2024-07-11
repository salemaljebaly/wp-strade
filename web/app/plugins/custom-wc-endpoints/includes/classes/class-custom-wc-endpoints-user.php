<?php
/**
 * Custom WC Endpoints User
 */
class Custom_WC_Endpoints_User {

    /**
     * Get the user's billing address
     *
     * @return WP_REST_Response
     */
    public function get_billing_address() {
        $user_id = get_current_user_id();
        $customer = new WC_Customer($user_id);
        $billing_address = $customer->get_billing();
        return new WP_REST_Response( $billing_address );
    }

    /**
     * Get the user's information
     *
     * @return WP_REST_Response
     */
    public function get_user_info() {
        $user_id = get_current_user_id();
        $customer = new WC_Customer($user_id);

        $user_info = array(
            'id'         => $customer->get_id(),
            'username'   => $customer->get_username(),
            'email'      => $customer->get_email(),
            'first_name' => $customer->get_first_name(),
            'last_name'  => $customer->get_last_name(),
            'role'       => $customer->get_role(),
            'city'       => $customer->get_billing_city(),
            'phone'      => $customer->get_billing_phone()
        );

        return new WP_REST_Response( $user_info );
    }
    
    /**
     * Delete User
     *
     * @return WP_REST_Response
     */
    public function delete_user() {
        $user_id = get_current_user_id();
        $customer = new WC_Customer($user_id);
        $customer->delete(true);
        return new WP_REST_Response( array(
            'message' => 'User deleted successfully'
         ) );
    }
}