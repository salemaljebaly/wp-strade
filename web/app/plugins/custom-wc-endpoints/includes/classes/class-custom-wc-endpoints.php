<?php
/**
 * Custom WC Endpoints base class
 */


 /**
  * Roles:
  * - diamond
  * - golden
  * - silver
  */
class Custom_WC_Endpoints {

    /**
     * Array of category names to exclude
     *
     * @var array
     */
    protected $golden_hidden_categories = array( 'ماسي' );
    protected $other_hidden_categories = array( 'ماسي',  'بضاعة جديدة', 'بضاعة جديده');

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Get category IDs based on category names
     *
     * @param array $category_names Array of category names
     * @return array Array of category IDs
     */
    protected function get_category_ids( $category_names ) {
        $category_ids = array_filter( array_map( function( $category_name ) {
            $term = get_term_by( 'name', $category_name, 'product_cat' );
            return $term ? $term->term_id : null;
        }, $category_names ) );

        return $category_ids;
    }

    /**
     * Get excluded categories for the given user
     *
     * @param array $roles
     * @return array
     */
    public function get_excluded_categories_for_user(array $roles)
    {
        $is_admin = count(array_intersect(['administrator', 'strade-admin', 'diamond'], $roles)) > 0;
        $is_golden = in_array('golden', $roles, true);

        if($is_admin){
            return array();
        } else if ($is_golden){
            return $this->get_category_ids($this->golden_hidden_categories);
        } else {
            return $this->get_category_ids($this->other_hidden_categories);
        }
    }

    /**
     * Check permission for the product endpoint
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'fetch-products' );
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
    }

    /**
     * Register custom endpoints
     */
    public function register_endpoints(): void
    {

        $register_controller = new Custom_WC_Endpoints_Register();
        $user_controller = new Custom_WC_Endpoints_User();
        $categories_controller = new Custom_WC_Endpoints_Categories();
        $checkoout_controller = new Custom_WC_Endpoints_Checkout();
        $products_controller = new Custom_WC_Endpoints_Products();

        
        $endpoints = [
            [
                'namespace' => 'aam/v2',
                'route' => '/register',
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$register_controller, 'register_user_endpoint_callback'],
                'permission_callback' => '__return_true',
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/user/billing-address',
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$user_controller, 'get_billing_address'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/user/profile',
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$user_controller, 'get_user_info'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/user/delete',
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$user_controller, 'delete_user'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/categories',
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$categories_controller, 'custom_categories_endpoint_callback'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/checkout',
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $checkoout_controller, 'handle_custom_checkout'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
            [
                'namespace' => 'lamah/v1',
                'route' => '/products',
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$products_controller, 'custom_product_endpoint_callback'],
                'permission_callback' => array( $this, 'check_permission' ),
            ],
        ];

        foreach ($endpoints as $endpoint) {
            $this->registerEndpoint($endpoint);
        }
    }

    public function registerEndpoint(array $endpointConfig): void
    {
        register_rest_route(
            $endpointConfig['namespace'],
            $endpointConfig['route'],
            [
                'methods' => $endpointConfig['methods'],
                'callback' => $endpointConfig['callback'],
                'permission_callback' => $endpointConfig['permission_callback'],
            ]
        );
    }

    /*
    // temporary code to replace the image uri with the original website (this method not used now)
    public static function replace_thumbnail_src($thumbnail_src) {
        $replacement = 'https://strade.ly';
        $current_url = home_url();
        $http = is_ssl() ? 'https://' : 'http://';
        
        $current_url = str_replace("http://", "", $current_url);
        $current_url = str_replace("https://", "", $current_url);
        
        $replacement_url = $http . $current_url;
        $new_thumbnail_src = str_replace($replacement_url, $replacement, $thumbnail_src);
        
        return $new_thumbnail_src;
    }
    */
}