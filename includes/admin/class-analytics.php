<?php
/**
 * Analytics Handler
 *
 * Handles analytics data processing and display.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/admin
 */

class Bracefox_BW_Analytics {

    /**
     * Stats repository
     */
    private $stats_repo;

    /**
     * Product repository
     */
    private $product_repo;

    /**
     * Constructor
     */
    public function __construct() {
        $this->stats_repo = new Bracefox_BW_Stats_Repository();
        $this->product_repo = new Bracefox_BW_Product_Repository();
    }

    /**
     * Get analytics data for dashboard
     *
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Analytics data
     */
    public function get_dashboard_data($start_date = null, $end_date = null) {
        $data = array(
            'total_conversations' => $this->stats_repo->get_total_conversations($start_date, $end_date),
            'conversion_rate' => $this->stats_repo->get_conversion_rate($start_date, $end_date),
            'session_stats' => $this->stats_repo->get_session_stats($start_date, $end_date),
            'avg_messages' => $this->stats_repo->get_avg_messages_per_session(),
            'top_products' => $this->get_enriched_top_products(10, $start_date, $end_date),
            'common_complaints' => $this->stats_repo->get_common_complaints(20),
        );

        return $data;
    }

    /**
     * Get top products with enriched product data
     *
     * @param int $limit Number of products
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Top products with data
     */
    private function get_enriched_top_products($limit, $start_date = null, $end_date = null) {
        $top_products = $this->stats_repo->get_top_products($limit, $start_date, $end_date);

        $enriched = array();

        foreach ($top_products as $product) {
            $product_id = $product['product_id'];
            $product_data = $this->product_repo->get_product($product_id);

            if ($product_data) {
                $enriched[] = array(
                    'id' => $product_id,
                    'name' => $product_data['name'],
                    'url' => $product_data['url'],
                    'image' => $product_data['image'],
                    'count' => $product['count'],
                );
            }
        }

        return $enriched;
    }

    /**
     * Format number for display
     *
     * @param mixed $number Number to format
     * @param int $decimals Decimal places
     * @return string Formatted number
     */
    public function format_number($number, $decimals = 0) {
        return number_format_i18n(floatval($number), $decimals);
    }

    /**
     * Format percentage for display
     *
     * @param float $percentage Percentage value
     * @return string Formatted percentage
     */
    public function format_percentage($percentage) {
        return $this->format_number($percentage, 1) . '%';
    }
}
