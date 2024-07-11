<?php
/**
 * Custom WC Endpoints Categories
 */
class Custom_WC_Endpoints_Categories extends Custom_WC_Endpoints  {
    /**
     * Custom categories endpoint callback
     */
    public function custom_categories_endpoint_callback() {
        $args = array(
            'taxonomy'     => 'product_cat',
            'parent'       => 0,
            'hide_empty'   => true,
            'exclude'      => array(),
        );

        $user = wp_get_current_user();
        $exclude_categories = $this->get_excluded_categories_for_user($user->roles);
        $categories = get_terms($args);
        $result = array();
      
        foreach ($categories as $category) {
            if (in_array($category->term_id, $exclude_categories)) {
                continue;
            }
      
            $count = $category->count;
      
            if ($count === 0) {
                continue;
            }
      
            $result[] = array(
                'id'  => $category->term_id,
                'name'  => $category->name,
                'count' => $count,
            );
        }
      
        return $result;
    }

}