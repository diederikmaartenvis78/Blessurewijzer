<?php
/**
 * Blog Repository
 *
 * Handles fetching and caching of blog posts.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/repositories
 */

class Bracefox_BW_Blog_Repository {

    /**
     * Cache service
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = new Bracefox_BW_Cache_Service();

        // Hook to invalidate cache when posts are updated
        add_action('save_post_post', array($this, 'invalidate_cache'));
    }

    /**
     * Get all blog posts
     *
     * @return array Array of blog post data
     */
    public function get_all_blogs() {
        return $this->cache->remember('blogs_all', function() {
            return $this->fetch_blogs();
        });
    }

    /**
     * Get single blog post by ID
     *
     * @param int $post_id Post ID
     * @return array|null Blog post data or null if not found
     */
    public function get_blog($post_id) {
        $cache_key = 'blog_' . $post_id;

        return $this->cache->remember($cache_key, function() use ($post_id) {
            return $this->fetch_single_blog($post_id);
        });
    }

    /**
     * Fetch blog posts from WordPress
     *
     * @return array Array of formatted blog post data
     */
    private function fetch_blogs() {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Limit to recent 50 posts
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $query = new WP_Query($args);
        $formatted_blogs = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $formatted = $this->format_blog(get_post());
                if ($formatted) {
                    $formatted_blogs[] = $formatted;
                }
            }
            wp_reset_postdata();
        }

        return $formatted_blogs;
    }

    /**
     * Fetch single blog post
     *
     * @param int $post_id Post ID
     * @return array|null Formatted blog post data or null
     */
    private function fetch_single_blog($post_id) {
        $post = get_post($post_id);

        if (!$post || $post->post_status !== 'publish') {
            return null;
        }

        return $this->format_blog($post);
    }

    /**
     * Format blog post data for AI consumption
     *
     * @param WP_Post $post WordPress post object
     * @return array Formatted blog post data
     */
    private function format_blog($post) {
        if (!$post) {
            return null;
        }

        // Get categories
        $categories = array();
        $category_terms = get_the_terms($post->ID, 'category');
        if ($category_terms && !is_wp_error($category_terms)) {
            foreach ($category_terms as $term) {
                $categories[] = $term->name;
            }
        }

        // Get tags
        $tags = array();
        $tag_terms = get_the_terms($post->ID, 'post_tag');
        if ($tag_terms && !is_wp_error($tag_terms)) {
            foreach ($tag_terms as $term) {
                $tags[] = $term->name;
            }
        }

        // Get excerpt
        $excerpt = get_the_excerpt($post);
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($post->post_content, 30, '...');
        }

        return array(
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => wp_strip_all_tags($excerpt),
            'categories' => $categories,
            'tags' => $tags,
            'url' => get_permalink($post),
            'date' => get_the_date('Y-m-d', $post),
        );
    }

    /**
     * Invalidate blog cache
     */
    public function invalidate_cache() {
        $this->cache->invalidate_blogs();
    }
}
