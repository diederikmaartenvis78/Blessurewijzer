<?php
/**
 * Analytics Dashboard Template
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/templates/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$analytics = new Bracefox_BW_Analytics();
$data = $analytics->get_dashboard_data();
?>

<div class="wrap bw-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="bw-analytics-grid">
        <!-- Stats Cards -->
        <div class="bw-stats-row">
            <div class="bw-stat-card">
                <div class="bw-stat-icon">💬</div>
                <div class="bw-stat-content">
                    <div class="bw-stat-value"><?php echo esc_html($analytics->format_number($data['total_conversations'])); ?></div>
                    <div class="bw-stat-label"><?php esc_html_e('Total Conversations', 'bracefox-blessurewijzer'); ?></div>
                </div>
            </div>

            <div class="bw-stat-card">
                <div class="bw-stat-icon">📈</div>
                <div class="bw-stat-content">
                    <div class="bw-stat-value"><?php echo esc_html($analytics->format_percentage($data['conversion_rate'])); ?></div>
                    <div class="bw-stat-label"><?php esc_html_e('Conversion Rate', 'bracefox-blessurewijzer'); ?></div>
                </div>
            </div>

            <div class="bw-stat-card">
                <div class="bw-stat-icon">✅</div>
                <div class="bw-stat-content">
                    <div class="bw-stat-value"><?php echo esc_html($analytics->format_number($data['session_stats']['completed'])); ?></div>
                    <div class="bw-stat-label"><?php esc_html_e('Completed Sessions', 'bracefox-blessurewijzer'); ?></div>
                </div>
            </div>

            <div class="bw-stat-card">
                <div class="bw-stat-icon">💬</div>
                <div class="bw-stat-content">
                    <div class="bw-stat-value"><?php echo esc_html($analytics->format_number($data['avg_messages'], 1)); ?></div>
                    <div class="bw-stat-label"><?php esc_html_e('Avg Messages/Session', 'bracefox-blessurewijzer'); ?></div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bw-dashboard-section">
            <h2><?php esc_html_e('Most Recommended Products', 'bracefox-blessurewijzer'); ?></h2>
            <?php if (!empty($data['top_products'])) : ?>
                <table class="bw-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'bracefox-blessurewijzer'); ?></th>
                            <th><?php esc_html_e('Recommendations', 'bracefox-blessurewijzer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['top_products'] as $product) : ?>
                            <tr>
                                <td>
                                    <div class="bw-product-cell">
                                        <?php if ($product['image']) : ?>
                                            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>" width="50" height="50">
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($product['url']); ?>" target="_blank">
                                            <?php echo esc_html($product['name']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo esc_html($product['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No data available yet.', 'bracefox-blessurewijzer'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Common Complaints -->
        <div class="bw-dashboard-section">
            <h2><?php esc_html_e('Common Complaints (Word Cloud)', 'bracefox-blessurewijzer'); ?></h2>
            <?php if (!empty($data['common_complaints'])) : ?>
                <div class="bw-word-cloud">
                    <?php
                    $max_count = max($data['common_complaints']);
                    foreach ($data['common_complaints'] as $word => $count) :
                        $size = 12 + (($count / $max_count) * 24);
                        ?>
                        <span class="bw-word" style="font-size: <?php echo esc_attr($size); ?>px;">
                            <?php echo esc_html($word); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('No data available yet.', 'bracefox-blessurewijzer'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
