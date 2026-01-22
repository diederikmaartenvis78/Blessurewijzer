<?php
/**
 * Product Repository
 *
 * Handles fetching and caching of WooCommerce products.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/repositories
 */

class Bracefox_BW_Product_Repository {

    /**
     * Cache service
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = new Bracefox_BW_Cache_Service();

        // Hook to invalidate cache when products are updated
        add_action('save_post_product', array($this, 'invalidate_cache'));
        add_action('woocommerce_update_product', array($this, 'invalidate_cache'));
    }

    /**
     * Get all products
     *
     * @return array Array of product data
     */
    public function get_all_products() {
        return $this->cache->remember('products_all', function() {
            return $this->fetch_products();
        });
    }

    /**
     * Get single product by ID
     *
     * @param int $product_id Product ID
     * @return array|null Product data or null if not found
     */
    public function get_product($product_id) {
        $cache_key = 'product_' . $product_id;

        return $this->cache->remember($cache_key, function() use ($product_id) {
            return $this->fetch_single_product($product_id);
        });
    }

    /**
     * Fetch products from WooCommerce
     *
     * @return array Array of formatted product data
     */
    private function fetch_products() {
        if (!function_exists('wc_get_products')) {
            return array();
        }

        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        );

        $products = wc_get_products($args);
        $formatted_products = array();

        foreach ($products as $product) {
            $formatted = $this->format_product($product);
            if ($formatted) {
                $formatted_products[] = $formatted;
            }
        }

        return $formatted_products;
    }

    /**
     * Fetch single product from WooCommerce
     *
     * @param int $product_id Product ID
     * @return array|null Formatted product data or null
     */
    private function fetch_single_product($product_id) {
        if (!function_exists('wc_get_product')) {
            return null;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return null;
        }

        return $this->format_product($product);
    }

    /**
     * Format product data for AI consumption
     *
     * @param WC_Product $product WooCommerce product object
     * @return array Formatted product data
     */
    private function format_product($product) {
        if (!$product || !$product->is_purchasable()) {
            return null;
        }

        // Get categories
        $categories = array();
        $category_terms = get_the_terms($product->get_id(), 'product_cat');
        if ($category_terms && !is_wp_error($category_terms)) {
            foreach ($category_terms as $term) {
                $categories[] = $term->name;
            }
        }

        // Get attributes as features
        $features = array();
        $attributes = $product->get_attributes();
        foreach ($attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());
                foreach ($terms as $term) {
                    $features[] = $term->name;
                }
            } else {
                $options = $attribute->get_options();
                foreach ($options as $option) {
                    $features[] = $option;
                }
            }
        }

        // Get description (use short description if available)
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = wp_trim_words($product->get_description(), 50, '...');
        }

        return array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => floatval($product->get_price()),
            'regular_price' => floatval($product->get_regular_price()),
            'sale_price' => $product->is_on_sale() ? floatval($product->get_sale_price()) : null,
            'categories' => $categories,
            'description' => wp_strip_all_tags($description),
            'features' => $features,
            'url' => get_permalink($product->get_id()),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
            'in_stock' => $product->is_in_stock(),
        );
    }

    /**
     * Invalidate product cache
     */
    public function invalidate_cache() {
        $this->cache->invalidate_products();
    }
}
