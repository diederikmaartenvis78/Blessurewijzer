<?php
/**
 * Chat Widget Template - Refactored v3.0
 *
 * Professional UI/UX implementation with:
 * - Single scroll container
 * - Sticky header and composer
 * - 8px grid spacing system
 * - Accessible markup
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/templates/frontend
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Prevent multiple widget instances on same page
static $widget_rendered = false;
if ($widget_rendered) {
    if (current_user_can('manage_options')) {
        echo '<div class="bw-error"><p>' . esc_html__('Blessurewijzer widget can only be used once per page.', 'bracefox-blessurewijzer') . '</p></div>';
    }
    return;
}
$widget_rendered = true;
?>

<div class="bw-widget" id="bw-widget">

    <!-- Disclaimer: Compact & Professional -->
    <div class="bw-disclaimer" role="region" aria-label="<?php esc_attr_e('Medical disclaimer', 'bracefox-blessurewijzer'); ?>">
        <div class="bw-disclaimer-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="bw-disclaimer-content">
            <p class="bw-disclaimer-text">
                <strong><?php esc_html_e('Let op:', 'bracefox-blessurewijzer'); ?></strong>
                <?php esc_html_e('Deze tool geeft algemene informatie en is geen vervanging voor professioneel medisch advies.', 'bracefox-blessurewijzer'); ?>
                <button type="button" class="bw-disclaimer-toggle" id="bw-disclaimer-toggle" aria-expanded="false" aria-controls="bw-disclaimer-expanded">
                    <?php esc_html_e('Meer info', 'bracefox-blessurewijzer'); ?>
                </button>
            </p>
            <div class="bw-disclaimer-expanded" id="bw-disclaimer-expanded">
                <ul>
                    <li><?php esc_html_e('Bij aanhoudende of ernstige klachten', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Bij verergering van de pijn', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Bij twijfel over de aard van je blessure', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Bij symptomen als zwelling, roodheid of warmte', 'bracefox-blessurewijzer'); ?></li>
                </ul>
                <p class="bw-disclaimer-footer">
                    <?php esc_html_e('Raadpleeg altijd een arts of fysiotherapeut. Bracefox is niet aansprakelijk voor schade door het opvolgen van dit advies.', 'bracefox-blessurewijzer'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Chat Container: Flexbox layout with sticky header/composer -->
    <div class="bw-chat-container">

        <!-- Header: Sticky -->
        <header class="bw-header">
            <div class="bw-header-content">
                <div>
                    <h2 class="bw-header-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="bw-header-subtitle"><?php esc_html_e('Beschrijf je klacht voor persoonlijk advies', 'bracefox-blessurewijzer'); ?></p>
                </div>
                <button type="button" class="bw-header-info" id="bw-header-info" aria-label="<?php esc_attr_e('Informatie', 'bracefox-blessurewijzer'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.061-1.061 3 3 0 112.871 5.026v.345a.75.75 0 01-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 108.94 6.94zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Chat Body: Single Scroll Container -->
        <div class="bw-chat-body" id="bw-chat-body">

            <!-- Welcome State -->
            <div class="bw-welcome" id="bw-welcome">
                <div class="bw-welcome-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                    </svg>
                </div>
                <h2><?php esc_html_e('Hoe kan ik je helpen?', 'bracefox-blessurewijzer'); ?></h2>
                <p><?php esc_html_e('Beschrijf je klacht en ik help je met het vinden van het juiste product en gratis hersteltips.', 'bracefox-blessurewijzer'); ?></p>
            </div>

            <!-- Messages Container -->
            <div class="bw-messages" id="bw-messages" role="log" aria-live="polite" aria-label="<?php esc_attr_e('Conversation', 'bracefox-blessurewijzer'); ?>">
                <!-- Messages are inserted here dynamically -->
            </div>

            <!-- Typing Indicator (hidden by default) -->
            <div class="bw-typing bw-hidden" id="bw-typing" aria-label="<?php esc_attr_e('Assistant is typing', 'bracefox-blessurewijzer'); ?>">
                <div class="bw-typing__bubble">
                    <span class="bw-typing__dot"></span>
                    <span class="bw-typing__dot"></span>
                    <span class="bw-typing__dot"></span>
                </div>
            </div>

            <!-- Loading Stepper (hidden by default) -->
            <div class="bw-stepper bw-hidden" id="bw-stepper" role="status" aria-label="<?php esc_attr_e('Loading progress', 'bracefox-blessurewijzer'); ?>">
                <ol class="bw-stepper__list">
                    <li class="bw-stepper__item bw-stepper__item--pending" data-step="1">
                        <span class="bw-stepper__indicator">1</span>
                        <span class="bw-stepper__label"><?php esc_html_e('Klacht analyseren', 'bracefox-blessurewijzer'); ?></span>
                    </li>
                    <li class="bw-stepper__item bw-stepper__item--pending" data-step="2">
                        <span class="bw-stepper__indicator">2</span>
                        <span class="bw-stepper__label"><?php esc_html_e('Product zoeken', 'bracefox-blessurewijzer'); ?></span>
                    </li>
                    <li class="bw-stepper__item bw-stepper__item--pending" data-step="3">
                        <span class="bw-stepper__indicator">3</span>
                        <span class="bw-stepper__label"><?php esc_html_e('Advies samenstellen', 'bracefox-blessurewijzer'); ?></span>
                    </li>
                </ol>
            </div>

        </div>

        <!-- Composer: Sticky -->
        <div class="bw-composer">
            <form id="bw-chat-form" class="bw-composer__form">
                <div class="bw-composer__input-wrapper">
                    <textarea
                        id="bw-input"
                        class="bw-composer__input"
                        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                        rows="1"
                        maxlength="500"
                        aria-label="<?php esc_attr_e('Your message', 'bracefox-blessurewijzer'); ?>"
                    ></textarea>
                </div>
                <button
                    type="submit"
                    class="bw-composer__send"
                    id="bw-send-button"
                    aria-label="<?php esc_attr_e('Send message', 'bracefox-blessurewijzer'); ?>"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
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
        <div class="bw-severity bw-hidden" data-element="severity">
            <div class="bw-severity__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="bw-severity__content">
                <h4><?php esc_html_e('Belangrijk', 'bracefox-blessurewijzer'); ?></h4>
                <p><?php esc_html_e('Op basis van je beschrijving raden we aan om contact op te nemen met een medisch professional.', 'bracefox-blessurewijzer'); ?></p>
            </div>
        </div>

        <!-- Product Recommendation -->
        <div class="bw-product" data-element="product">
            <div class="bw-product__header">
                <span class="bw-product__header-icon" aria-hidden="true">🛒</span>
                <h3 class="bw-product__header-title"><?php esc_html_e('Aanbevolen Product', 'bracefox-blessurewijzer'); ?></h3>
            </div>
            <div class="bw-product__body">
                <div class="bw-product__image">
                    <img src="" alt="" data-element="product-image">
                </div>
                <div class="bw-product__details">
                    <h4 class="bw-product__name" data-element="product-name"></h4>
                    <p class="bw-product__reasoning" data-element="product-reasoning"></p>
                    <div class="bw-product__footer">
                        <span class="bw-product__price" data-element="product-price"></span>
                        <a href="#" class="bw-product__button" target="_blank" rel="noopener" data-element="product-link">
                            <?php esc_html_e('Bekijk product', 'bracefox-blessurewijzer'); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Advice -->
        <div class="bw-health" data-element="health">
            <div class="bw-health__header">
                <span class="bw-health__header-icon" aria-hidden="true">💪</span>
                <h3 class="bw-health__header-title"><?php esc_html_e('Gratis Herstel Tips', 'bracefox-blessurewijzer'); ?></h3>
            </div>
            <div class="bw-health__grid">

                <!-- Exercises -->
                <div class="bw-health__card" data-element="exercises-card">
                    <div class="bw-health__card-header">
                        <span class="bw-health__card-icon" aria-hidden="true">🏃</span>
                        <h4 class="bw-health__card-title"><?php esc_html_e('Oefeningen', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-health__card-content" data-element="exercises-list">
                        <!-- Exercises inserted here -->
                    </div>
                </div>

                <!-- Thermal Advice -->
                <div class="bw-health__card" data-element="thermal-card">
                    <div class="bw-health__card-header">
                        <span class="bw-health__card-icon" aria-hidden="true">🌡️</span>
                        <h4 class="bw-health__card-title"><?php esc_html_e('Koelen/Verwarmen', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-health__card-content" data-element="thermal-content">
                        <!-- Thermal advice inserted here -->
                    </div>
                </div>

                <!-- Rest Advice -->
                <div class="bw-health__card" data-element="rest-card">
                    <div class="bw-health__card-header">
                        <span class="bw-health__card-icon" aria-hidden="true">⏸️</span>
                        <h4 class="bw-health__card-title"><?php esc_html_e('Rust advies', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-health__card-content" data-element="rest-content">
                        <!-- Rest advice inserted here -->
                    </div>
                </div>

                <!-- Lifestyle Tips -->
                <div class="bw-health__card" data-element="lifestyle-card">
                    <div class="bw-health__card-header">
                        <span class="bw-health__card-icon" aria-hidden="true">✨</span>
                        <h4 class="bw-health__card-title"><?php esc_html_e('Extra tips', 'bracefox-blessurewijzer'); ?></h4>
                    </div>
                    <div class="bw-health__card-content" data-element="lifestyle-content">
                        <!-- Lifestyle tips inserted here -->
                    </div>
                </div>

            </div>
        </div>

        <!-- Related Blogs -->
        <div class="bw-blogs bw-hidden" data-element="blogs">
            <div class="bw-blogs__header">
                <span class="bw-blogs__header-icon" aria-hidden="true">📚</span>
                <h3 class="bw-blogs__header-title"><?php esc_html_e('Relevante Artikelen', 'bracefox-blessurewijzer'); ?></h3>
            </div>
            <div class="bw-blogs__list" data-element="blogs-list">
                <!-- Blogs inserted here -->
            </div>
        </div>

        <!-- New Question Button -->
        <div class="bw-new-question">
            <button type="button" class="bw-new-question__button" id="bw-new-question">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
                <?php esc_html_e('Stel een andere vraag', 'bracefox-blessurewijzer'); ?>
            </button>
        </div>

    </div>
</template>
