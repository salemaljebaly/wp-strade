<?php
/**
 * Handles the registration of new user accounts.
 */
class Custom_WC_Endpoints_Register {

    /**
     * Registers the new user account.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response or an error object.
     */
    public function register_user_endpoint_callback( $request ) {
        $parameters = $request->get_json_params();
        $username   = sanitize_user( $parameters['username'] );
        $email      = sanitize_email( $parameters['email'] );
        $password   = $parameters['password'];

        $errors = new WP_Error();

        // Validate the input.
        if ( empty( $username ) ) {
            $errors->add( 400, __( "Username field 'username' is required.", 'custom-wc-endpoints' ), array( 'status' => 400 ) );
            return $errors;
        }

        if ( empty( $email ) ) {
            $errors->add( 401, __( "Email field 'email' is required.", 'custom-wc-endpoints' ), array( 'status' => 400 ) );
            return $errors;
        }

        if ( empty( $password ) ) {
            $errors->add( 404, __( "Password field 'password' is required.", 'custom-wc-endpoints' ), array( 'status' => 400 ) );
            return $errors;
        }

        // Check if the username or email already exists.
        if ( username_exists( $username ) || email_exists( $email ) ) {
            $errors->add( 406, __( "Email or Username already exists, please try 'Reset Password'", 'custom-wc-endpoints' ), array( 'status' => 400 ) );
            return $errors;
        }


        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        $user = get_user_by( 'id', $user_id );
        error_log('userid');
        error_log(print_r($user,true));
        error_log('url');
        error_log(print_r(get_rest_url( null, 'aam/v2/authenticate' ),true));
        $user->set_role( 'golden' );

        $response = array(
            'code'    => 200,
            'message' => __( "User '" . $username . "' Registration was Successful", 'custom-wc-endpoints' ),
        );

        // Authenticate the user after successful registration.
        return $this->authenticate_with_api( $username, $password );
    }

    /**
     * Authenticates the user with the API.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return WP_REST_Response The response.
     */
    private function authenticate_with_api( $username, $password ) {
        $request_body = array(
            'username' => $username,
            'password' => $password,
            'issueJWT' => true,
            "refreshableJWT" => true
        );

        if ( class_exists( 'AAM_Service_SecureLogin' ) && method_exists( 'AAM_Service_SecureLogin', 'authenticate' ) ) {
            $response = wp_remote_post(
                get_rest_url( null, 'aam/v2/authenticate' ),
                array(
                    'method' => 'POST',
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                    'body' => wp_json_encode( $request_body )
                )
            );
            error_log(print_r($response, true));
        } else {
            return new WP_Error( 'authentication_error', 'Error in authentication', array( 'status' => 500 ) );
        }

        // Check if the request was successful.
        if ( is_wp_error( $response ) ) {
            return $response;
        } else {
            $response_body = wp_remote_retrieve_body( $response );
            $response_data = json_decode($response_body, true);
            return new WP_REST_Response( $response_data, 200 );
        }
    }
}