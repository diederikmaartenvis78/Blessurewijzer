<?php
/**
 * Chat Widget Template
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/templates/frontend
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="bw-widget" id="bw-widget">
    <!-- Disclaimer -->
    <div class="bw-disclaimer">
        <div class="bw-disclaimer-icon">⚠️</div>
        <div class="bw-disclaimer-content">
            <h3><?php esc_html_e('Belangrijke informatie', 'bracefox-blessurewijzer'); ?></h3>
            <p>
                <?php esc_html_e('Deze blessurewijzer geeft algemeen advies en is GEEN vervanging voor professioneel medisch advies, diagnose of behandeling.', 'bracefox-blessurewijzer'); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Raadpleeg altijd een arts, fysiotherapeut of andere medische professional:', 'bracefox-blessurewijzer'); ?></strong>
            </p>
            <ul>
                <li><?php esc_html_e('Bij aanhoudende of ernstige klachten', 'bracefox-blessurewijzer'); ?></li>
                <li><?php esc_html_e('Bij verergering van de pijn', 'bracefox-blessurewijzer'); ?></li>
                <li><?php esc_html_e('Bij twijfel over de aard van je blessure', 'bracefox-blessurewijzer'); ?></li>
                <li><?php esc_html_e('Bij symptomen als zwelling, roodheid of warmte', 'bracefox-blessurewijzer'); ?></li>
                <li><?php esc_html_e('Na een ongeluk of trauma', 'bracefox-blessurewijzer'); ?></li>
            </ul>
            <p class="bw-disclaimer-footer">
                <?php esc_html_e('Bracefox is niet aansprakelijk voor eventuele schade die voortvloeit uit het opvolgen van dit advies.', 'bracefox-blessurewijzer'); ?>
            </p>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="bw-chat-container">
        <!-- Welcome Message -->
        <div class="bw-welcome-message" id="bw-welcome">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p><?php esc_html_e('Beschrijf je klacht en ik help je met het vinden van het juiste product en geef je gratis herstel tips.', 'bracefox-blessurewijzer'); ?></p>
        </div>

        <!-- Messages -->
        <div class="bw-messages" id="bw-messages">
            <!-- Messages will be added here dynamically -->
        </div>

        <!-- Loading Indicator -->
        <div class="bw-loading" id="bw-loading" style="display: none;">
            <div class="bw-loading-simple" id="bw-loading-simple">
                <div class="bw-loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <p><?php esc_html_e('Even denken...', 'bracefox-blessurewijzer'); ?></p>
            </div>

            <div class="bw-loading-premium" id="bw-loading-premium" style="display: none;">
                <div class="bw-loading-step" data-step="1">
                    <div class="bw-loading-icon">🔍</div>
                    <div class="bw-loading-step-content">
                        <h4><?php esc_html_e('Klacht analyseren', 'bracefox-blessurewijzer'); ?></h4>
                        <div class="bw-loading-progress">
                            <div class="bw-loading-progress-bar"></div>
                        </div>
                    </div>
                    <div class="bw-loading-check">✓</div>
                </div>

                <div class="bw-loading-step" data-step="2">
                    <div class="bw-loading-icon">🛍️</div>
                    <div class="bw-loading-step-content">
                        <h4><?php esc_html_e('Beste product zoeken', 'bracefox-blessurewijzer'); ?></h4>
                        <div class="bw-loading-progress">
                            <div class="bw-loading-progress-bar"></div>
                        </div>
                    </div>
                    <div class="bw-loading-check">✓</div>
                </div>

                <div class="bw-loading-step" data-step="3">
                    <div class="bw-loading-icon">💡</div>
                    <div class="bw-loading-step-content">
                        <h4><?php esc_html_e('Advies samenstellen', 'bracefox-blessurewijzer'); ?></h4>
                        <div class="bw-loading-progress">
                            <div class="bw-loading-progress-bar"></div>
                        </div>
                    </div>
                    <div class="bw-loading-check">✓</div>
                </div>
            </div>
        </div>

        <!-- Input Form -->
        <div class="bw-input-container">
            <form id="bw-chat-form">
                <textarea
                    id="bw-input"
                    class="bw-input"
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                    rows="1"
                    maxlength="500"
                ></textarea>
                <button type="submit" class="bw-send-button" id="bw-send-button">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 10L18 2L10 18L9 11L2 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Hidden template for advice card -->
<template id="bw-advice-template">
    <div class="bw-advice-card">
        <!-- Severity Warning (conditional) -->
        <div class="bw-severity-warning" style="display: none;">
            <div class="bw-severity-icon">🚨</div>
            <div class="bw-severity-content">
                <h4><?php esc_html_e('Belangrijk', 'bracefox-blessurewijzer'); ?></h4>
                <p><?php esc_html_e('Op basis van je beschrijving raden we aan om direct contact op te nemen met een medisch professional. De symptomen die je beschrijft kunnen wijzen op een ernstiger probleem dat professionele beoordeling vereist.', 'bracefox-blessurewijzer'); ?></p>
            </div>
        </div>

        <!-- Product Recommendation -->
        <div class="bw-product-section">
            <h3><?php esc_html_e('🛒 Aanbevolen Product', 'bracefox-blessurewijzer'); ?></h3>
            <div class="bw-product-card">
                <div class="bw-product-image">
                    <img src="" alt="">
                </div>
                <div class="bw-product-details">
                    <h4 class="bw-product-name"></h4>
                    <p class="bw-product-reasoning"></p>
                    <div class="bw-product-footer">
                        <span class="bw-product-price"></span>
                        <a href="#" class="bw-product-button" target="_blank">
                            <?php esc_html_e('Bekijk product', 'bracefox-blessurewijzer'); ?> →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Advice -->
        <div class="bw-health-section">
            <h3><?php esc_html_e('💪 Gratis Herstel Tips', 'bracefox-blessurewijzer'); ?></h3>

            <div class="bw-health-grid">
                <!-- Exercises -->
                <div class="bw-health-card">
                    <div class="bw-health-card-header">
                        <span class="bw-health-icon">🏃</span>
                        <h4><?php esc_html_e('Oefeningen', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-exercises-list">
                        <!-- Exercises will be inserted here -->
                    </div>
                </div>

                <!-- Thermal Advice -->
                <div class="bw-health-card">
                    <div class="bw-health-card-header">
                        <span class="bw-health-icon">🌡️</span>
                        <h4><?php esc_html_e('Koelen/Verwarmen', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-thermal-content">
                        <!-- Thermal advice will be inserted here -->
                    </div>
                </div>

                <!-- Rest Advice -->
                <div class="bw-health-card">
                    <div class="bw-health-card-header">
                        <span class="bw-health-icon">⏸️</span>
                        <h4><?php esc_html_e('Rust advies', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-rest-content">
                        <!-- Rest advice will be inserted here -->
                    </div>
                </div>

                <!-- Lifestyle Tips -->
                <div class="bw-health-card">
                    <div class="bw-health-card-header">
                        <span class="bw-health-icon">✨</span>
                        <h4><?php esc_html_e('Extra tips', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-lifestyle-content">
                        <!-- Lifestyle tips will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Blogs -->
        <div class="bw-blogs-section" style="display: none;">
            <h3><?php esc_html_e('📚 Relevante Artikelen', 'bracefox-blessurewijzer'); ?></h3>
            <div class="bw-blogs-list">
                <!-- Related blogs will be inserted here -->
            </div>
        </div>

        <!-- New Question Button -->
        <div class="bw-new-question-container">
            <button type="button" class="bw-new-question-button" id="bw-new-question">
                ← <?php esc_html_e('Stel een andere vraag', 'bracefox-blessurewijzer'); ?>
            </button>
        </div>
    </div>
</template>
