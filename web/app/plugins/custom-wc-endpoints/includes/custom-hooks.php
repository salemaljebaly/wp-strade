<?php

/**
 * Custom Hooks
 *
 * @package customWcEndpoints
 */

/**
 * Filter the authentication response to include 'roles' field in user data.
 *
 * @param array $response The current response array.
 * @param WP_REST_Request $request The current REST request object.
 * @param WP_User $user The current user object.
 *
 * @return array The modified response array with additional user data.
 */
function custom_prepare_user_data(
    array $response,
    WP_REST_Request $request,
    $user
) {

    $modified_response['jwt_token'] = $response['jwt']['token'];
    return $modified_response;
}
add_filter('aam_auth_response_filter', 'custom_prepare_user_data', 20, 3);


/**
 * Modify the authentication result and status code.
 *
 * @param array $result The current result array.
 * @param int $status The current HTTP status code.
 * @param WP_REST_Request $request The current REST request object.
 *
 * @return array The modified result and status code.
 */
function custom_authenticate_filter_callback($result, $status, $request)
{
    $modified_result = $result;
    $modified_status = $status;

    if (isset($result['jwt_token'])) {
        $jwtToken = $result['jwt_token'];

        // Split the JWT token into its three parts: header, payload, and signature
        $jwtParts = explode('.', $jwtToken);

        // Ensure that the JWT token has three parts
        if (count($jwtParts) === 3) {
            $payloadBase64 = $jwtParts[1];

            $payloadJson = base64_decode($payloadBase64);

            $payloadData = json_decode($payloadJson, true);

            if(isset($payloadData['roles'])){

                $roles = $payloadData['roles'];
                if (in_array('suspended', $roles, true)) {
                    // User has the 'suspended' role, modify the response
                    $modified_result = array(
                        'code'    => 'account_suspended',
                        'message' => 'Your account has been suspended. Please contact the administrator for further assistance.',
                        'data'    => array(
                            'status' => 403,
                        ),
                    );
                    $modified_status = 403;
              }

            }
        }
    }

    return array(
        'result' => $modified_result,
        'status' => $modified_status,
    );
}
add_filter('custom_authenticate_filter', 'custom_authenticate_filter_callback', 10, 3);


function additional_items_data($items)
{
    $new_items = array();

    foreach ($items as $item) {
        $product_id = $item['id'];
        $product_data = Custom_WC_Endpoints_Products::get_product_by_id($product_id);

        $new_item = array(
            'item_key' => $item['item_key'],
            'id' => $product_data['id'],
            'quantity' => $item['quantity'],
            'totals' => $item['totals'],
            'name' => $product_data['name'],
            'slug' => $product_data['slug'],
            'featuredImage' => $product_data['featuredImage'],
            'category' => $product_data['category'],
            'cartonPrice' => $product_data['cartonPrice'],
            'singlePrice' => $product_data['singlePrice'],
            'pieces' => $product_data['pieces']
        );

        $new_items[] = $new_item;
    }

    return $new_items;
}

add_filter('cocart_cart_items', 'additional_items_data', 10, 4);

function additional_user_data_jwt($claims)
{
    $user = get_user_by('ID', $claims['userId']);
    // unset($claims['revocable']);
    // unset($claims['refreshable']);
    // unset($claims['iat']);

    $claims['username'] = $user->user_login;
    $claims['display_name'] = $user->display_name;
    $claims['user_email'] = $user->user_email;
    $claims['roles'] = $user->roles;

    return $claims;
}
add_filter('aam_jwt_claims_filter', 'additional_user_data_jwt', 10, 1);


function disable_api_for_suspended($result, $server, $request) {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('suspended', (array) $user->roles)) {
            return new WP_Error(
                'account_suspended',
                __('Your account has been suspended. Please contact the administrator for further assistance.'),
                array('status' => 403)
            );
        }
    }
    return $result;
}
add_filter('rest_pre_dispatch', 'disable_api_for_suspended', 10, 3);


function add_cors_http_header() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        exit(0);
    }
}

// Apply the CORS headers to specific endpoints
add_action('rest_api_init', function() {
    // Only add the CORS headers to the specific CoCart endpoint
    if (strpos($_SERVER['REQUEST_URI'], '/wp-json/cocart/v2/') !== false) {
        add_cors_http_header();
    }
}, 15);
