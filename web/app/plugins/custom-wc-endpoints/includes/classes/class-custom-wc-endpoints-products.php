<?php

/**
 * Custom WC Endpoints Products
 */
class Custom_WC_Endpoints_Products extends Custom_WC_Endpoints
{

    /**
     * Custom product endpoint callback
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function custom_product_endpoint_callback(WP_REST_Request $request)
    {
 
        $user = wp_get_current_user();
        $exclude_categories = $this->get_excluded_categories_for_user($user->roles);
        $page = (int)$request->get_param('page') ?: 1;
        $per_page = (int)$request->get_param('per_page') ?: 12;
        $category = $request->get_param('category');
        $category_title_param = $request->get_param('categoryName');
        $category_title_param = is_null($category_title_param) ? null : trim($category_title_param);
        $category_id = is_null($category) ? null : (int)$category;
        $search = $request->get_param('search');
        $search = is_null($search) ? null : trim($search);

        $offset = ($page - 1) * $per_page;

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
        );

        if (is_null($category_id) && !is_null($category_title_param)) {
            $category = get_term_by('name', $category_title_param, 'product_cat');

            if ($category) {
                $category_id = $category->term_id;
            } else {
                $category_id  = 0;
            }
        }

        if ($category_id === 0) {
            return new WP_Error('error_in_category', 'Please provide a valid category ID.', array('status' => 422));
        }

        if (!is_null($category_id)) {
            if (!in_array($category_id, $exclude_categories )) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $category_id,
                    ),
                );
            } else {
                return [];
            }
        } else {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $exclude_categories,
                    'operator' => 'NOT IN',
                ),
            );
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $products = new WP_Query($args);

        $result = array();
        while ($products->have_posts()) {
            $products->the_post();
            $product_id = get_the_ID();
            $product_data = $this->get_product_by_id($product_id);
            $result[] = $product_data;
        }

        $total_pages = ceil($products->found_posts / $per_page);
        $response = new WP_REST_Response($result);
        $response->header('x-wp-totalpages', $total_pages);

        return $response;
      
    }


    /**
     * Get product data by ID.
     *
     * @param int $product_id The ID of the product.
     * @return array The product data.
     */
    public static function get_product_by_id($product_id) {
        $product_data = wc_get_product($product_id);
    
        $category_name = '';
        $categories = get_the_terms($product_id, 'product_cat');
        if (!is_wp_error($categories) && !empty($categories)) {
            $category_name = $categories[0]->name;
        }
    
        $featured_image_url = get_the_post_thumbnail_url($product_id, 'full');
        if (!$featured_image_url) {
            $featured_image_url = wc_placeholder_img_src('full');
        }
    
        // temporary code to replace the image uri with the original website
        // $new_featured_image_url  = self::replace_thumbnail_src($featured_image_url);
    
        $result = array(
            'id'            => $product_data->get_id(),
            'name'          => $product_data->get_name(),
            'slug'          => $product_data->get_slug(),
            'featuredImage' => $featured_image_url,
            'category'      => $category_name,
            'cartonPrice'   => $product_data->get_price(),
            'singlePrice'   => get_post_meta($product_id, 'singleprice', true),
            'pieces'        => get_post_meta($product_id, 'pice', true),
        );
    
        return $result;
    }
}
