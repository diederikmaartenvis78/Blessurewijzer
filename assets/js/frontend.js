/**
 * Bracefox Blessurewijzer 3.0 - Frontend JavaScript
 * Professional UI/UX implementation
 *
 * Features:
 * - Smart auto-scroll (only when near bottom)
 * - Respects prefers-reduced-motion
 * - Proper loading states with stepper
 * - Singleton pattern for widget
 */

(function($) {
    'use strict';

    /**
     * Check if user prefers reduced motion
     */
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /**
     * Main Chat Handler
     */
    const BracefoxChat = {
        // State
        sessionId: null,
        conversationHistory: [],
        isProcessing: false,
        loadingTimeout: null,
        stepperTimeout: null,

        // DOM elements
        elements: {},

        // Scroll threshold (pixels from bottom to auto-scroll)
        scrollThreshold: 100,

        /**
         * Initialize the chat
         */
        init: function() {
            // Cache DOM elements
            this.elements = {
                widget: $('#bw-widget'),
                form: $('#bw-chat-form'),
                input: $('#bw-input'),
                sendButton: $('#bw-send-button'),
                chatBody: $('#bw-chat-body'),
                messages: $('#bw-messages'),
                welcome: $('#bw-welcome'),
                typing: $('#bw-typing'),
                stepper: $('#bw-stepper'),
                disclaimerToggle: $('#bw-disclaimer-toggle'),
                disclaimerExpanded: $('#bw-disclaimer-expanded')
            };

            // Only initialize if widget exists
            if (this.elements.widget.length === 0) {
                return;
            }

            this.bindEvents();
            this.initTextareaResize();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Form submission
            this.elements.form.on('submit', function(e) {
                e.preventDefault();
                self.handleSendMessage();
            });

            // Enter to send (Shift+Enter for new line)
            this.elements.input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.elements.form.trigger('submit');
                }
            });

            // Textarea auto-resize on input
            this.elements.input.on('input', function() {
                self.resizeTextarea();
            });

            // New question button (delegated)
            this.elements.widget.on('click', '#bw-new-question', function(e) {
                e.preventDefault();
                self.handleNewQuestion();
            });

            // Product click tracking (delegated)
            this.elements.widget.on('click', '.bw-product__button', function(e) {
                const productId = $(this).data('product-id');
                if (productId) {
                    self.trackProductClick(productId);
                }
            });

            // Disclaimer toggle
            this.elements.disclaimerToggle.on('click', function() {
                self.toggleDisclaimer();
            });
        },

        /**
         * Toggle disclaimer expanded state
         */
        toggleDisclaimer: function() {
            const $expanded = this.elements.disclaimerExpanded;
            const $toggle = this.elements.disclaimerToggle;
            const isExpanded = $expanded.hasClass('is-visible');

            if (isExpanded) {
                $expanded.removeClass('is-visible');
                $toggle.attr('aria-expanded', 'false');
                $toggle.text(bracefoxBW.i18n.more_info || 'Meer info');
            } else {
                $expanded.addClass('is-visible');
                $toggle.attr('aria-expanded', 'true');
                $toggle.text(bracefoxBW.i18n.less_info || 'Minder info');
            }
        },

        /**
         * Initialize textarea auto-resize
         */
        initTextareaResize: function() {
            // Set initial height
            this.resizeTextarea();
        },

        /**
         * Resize textarea based on content
         */
        resizeTextarea: function() {
            const textarea = this.elements.input[0];
            if (!textarea) return;

            // Reset height to auto to get correct scrollHeight
            textarea.style.height = 'auto';

            // Calculate new height (max 120px as defined in CSS)
            const newHeight = Math.min(textarea.scrollHeight, 120);
            textarea.style.height = newHeight + 'px';
        },

        /**
         * Handle sending a message
         */
        handleSendMessage: function() {
            if (this.isProcessing) {
                return;
            }

            const message = this.elements.input.val().trim();

            if (!message) {
                this.showError(bracefoxBW.i18n.error_empty_message);
                return;
            }

            // Add user message to UI
            this.addUserMessage(message);

            // Add to conversation history
            this.conversationHistory.push({
                role: 'user',
                content: message
            });

            // Clear input and reset height
            this.elements.input.val('');
            this.resizeTextarea();

            // Hide welcome message
            this.hideWelcome();

            // Send to server
            this.sendToAI(message);
        },

        /**
         * Hide welcome message
         */
        hideWelcome: function() {
            const $welcome = this.elements.welcome;
            if ($welcome.is(':visible')) {
                if (prefersReducedMotion) {
                    $welcome.hide();
                } else {
                    $welcome.fadeOut(200);
                }
            }
        },

        /**
         * Send message to AI via AJAX
         */
        sendToAI: function(message) {
            const self = this;

            this.isProcessing = true;
            this.elements.sendButton.prop('disabled', true);
            this.showTypingIndicator();

            // Switch to stepper after 2 seconds
            this.loadingTimeout = setTimeout(function() {
                self.showStepper();
            }, 2000);

            $.ajax({
                url: bracefoxBW.ajax_url,
                type: 'POST',
                data: {
                    action: 'bw_send_message',
                    nonce: bracefoxBW.nonce,
                    message: message,
                    session_id: this.sessionId,
                    history: JSON.stringify(this.conversationHistory)
                },
                success: function(response) {
                    if (response.success) {
                        self.handleAIResponse(response.data);
                    } else {
                        self.handleError(response.data);
                    }
                },
                error: function(jqXHR) {
                    if (jqXHR.status === 429) {
                        self.showError(bracefoxBW.i18n.error_rate_limited);
                    } else {
                        self.showError(bracefoxBW.i18n.error_generic);
                    }
                },
                complete: function() {
                    self.isProcessing = false;
                    self.elements.sendButton.prop('disabled', false);
                    self.hideLoadingStates();
                }
            });
        },

        /**
         * Show typing indicator
         */
        showTypingIndicator: function() {
            this.elements.typing.removeClass('bw-hidden');
            this.scrollToBottomIfNeeded();
        },

        /**
         * Show stepper for long-running requests
         */
        showStepper: function() {
            const self = this;

            // Hide typing, show stepper
            this.elements.typing.addClass('bw-hidden');
            this.elements.stepper.removeClass('bw-hidden');

            // Reset stepper state
            this.elements.stepper.find('.bw-stepper__item')
                .removeClass('bw-stepper__item--active bw-stepper__item--done')
                .addClass('bw-stepper__item--pending');

            // Animate through steps
            this.animateStep(1, 0);
            this.animateStep(2, 1200);
            this.animateStep(3, 2400);

            this.scrollToBottomIfNeeded();
        },

        /**
         * Animate a stepper step
         */
        animateStep: function(stepNumber, delay) {
            const self = this;

            this.stepperTimeout = setTimeout(function() {
                if (!self.isProcessing) return;

                const $steps = self.elements.stepper.find('.bw-stepper__item');

                // Mark previous steps as done
                $steps.filter(function() {
                    return $(this).data('step') < stepNumber;
                }).removeClass('bw-stepper__item--pending bw-stepper__item--active')
                  .addClass('bw-stepper__item--done')
                  .find('.bw-stepper__indicator').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>');

                // Mark current step as active
                $steps.filter('[data-step="' + stepNumber + '"]')
                    .removeClass('bw-stepper__item--pending')
                    .addClass('bw-stepper__item--active');

            }, delay);
        },

        /**
         * Hide all loading states
         */
        hideLoadingStates: function() {
            // Clear timeouts
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }
            if (this.stepperTimeout) {
                clearTimeout(this.stepperTimeout);
                this.stepperTimeout = null;
            }

            // Hide indicators
            this.elements.typing.addClass('bw-hidden');
            this.elements.stepper.addClass('bw-hidden');

            // Reset stepper
            this.elements.stepper.find('.bw-stepper__item')
                .removeClass('bw-stepper__item--active bw-stepper__item--done')
                .addClass('bw-stepper__item--pending')
                .find('.bw-stepper__indicator').each(function(index) {
                    $(this).text(index + 1);
                });
        },

        /**
         * Handle AI response
         */
        handleAIResponse: function(data) {
            // Store session ID
            if (data.session_id) {
                this.sessionId = data.session_id;
            }

            const response = data.response;

            // Add to conversation history
            this.conversationHistory.push({
                role: 'assistant',
                content: JSON.stringify(response)
            });

            // Determine response type
            if (response.message_type === 'question') {
                this.addAssistantMessage(response.personal_message);
            } else if (response.message_type === 'advice') {
                this.renderAdviceCard(response);
            }

            this.scrollToBottomIfNeeded();
        },

        /**
         * Handle error response
         */
        handleError: function(data) {
            const message = data.message || bracefoxBW.i18n.error_generic;
            this.showError(message);
        },

        /**
         * Add user message to chat
         */
        addUserMessage: function(message) {
            const html = '<div class="bw-message bw-message--user">' +
                '<div class="bw-message__bubble--user">' + this.escapeHtml(message) + '</div>' +
                '</div>';

            this.elements.messages.append(html);
            this.scrollToBottomIfNeeded();
        },

        /**
         * Add assistant message to chat
         */
        addAssistantMessage: function(message) {
            const html = '<div class="bw-message bw-message--assistant">' +
                '<div class="bw-message__bubble--assistant">' + this.escapeHtml(message) + '</div>' +
                '</div>';

            this.elements.messages.append(html);
            this.scrollToBottomIfNeeded();
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const html = '<div class="bw-error">' +
                '<div class="bw-error__icon">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">' +
                '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />' +
                '</svg>' +
                '</div>' +
                '<p class="bw-error__message">' + this.escapeHtml(message) + '</p>' +
                '</div>';

            this.elements.messages.append(html);
            this.scrollToBottomIfNeeded();
        },

        /**
         * Render full advice card
         */
        renderAdviceCard: function(advice) {
            const self = this;
            const $template = $('#bw-advice-template');
            const $card = $($template.html()).clone();

            // Personal message first
            if (advice.personal_message) {
                this.addAssistantMessage(advice.personal_message);
            }

            // Severity warning
            if (advice.severity_warning) {
                $card.find('[data-element="severity"]').removeClass('bw-hidden');
            }

            // Product recommendation
            if (advice.product_recommendation && advice.product_recommendation.product_data) {
                const product = advice.product_recommendation.product_data;
                const reasoning = advice.product_recommendation.reasoning;

                $card.find('[data-element="product-image"]').attr({
                    src: product.image,
                    alt: product.name
                });
                $card.find('[data-element="product-name"]').text(product.name);
                $card.find('[data-element="product-reasoning"]').text(reasoning);
                $card.find('[data-element="product-price"]').text('€' + parseFloat(product.price).toFixed(2).replace('.', ','));
                $card.find('[data-element="product-link"]')
                    .attr('href', product.url)
                    .data('product-id', product.id);
            } else {
                $card.find('[data-element="product"]').hide();
            }

            // Health advice
            if (advice.health_advice) {
                const health = advice.health_advice;

                // Exercises
                if (health.exercises && health.exercises.length > 0) {
                    const $exercisesList = $card.find('[data-element="exercises-list"]');
                    $exercisesList.empty();

                    health.exercises.forEach(function(exercise) {
                        const exerciseHtml = '<div class="bw-exercise">' +
                            '<div class="bw-exercise__name">' + self.escapeHtml(exercise.name) + '</div>' +
                            '<div class="bw-exercise__description">' + self.escapeHtml(exercise.description) + '</div>' +
                            '<div class="bw-exercise__meta">' +
                            self.escapeHtml(exercise.duration) + ' · ' +
                            self.escapeHtml(exercise.frequency) +
                            '</div>' +
                            '</div>';
                        $exercisesList.append(exerciseHtml);
                    });
                } else {
                    $card.find('[data-element="exercises-card"]').hide();
                }

                // Thermal advice
                if (health.thermal_advice) {
                    const thermal = health.thermal_advice;
                    const methodIcon = thermal.method === 'koelen' ? '❄️' : '🔥';
                    const methodText = thermal.method === 'koelen' ? 'Koelen' : 'Verwarmen';

                    const thermalHtml = '<p><strong>' + methodIcon + ' ' + methodText + '</strong></p>' +
                        '<p>' + this.escapeHtml(thermal.explanation) + '</p>' +
                        '<p><em>' + this.escapeHtml(thermal.duration) + '</em></p>';

                    $card.find('[data-element="thermal-content"]').html(thermalHtml);
                } else {
                    $card.find('[data-element="thermal-card"]').hide();
                }

                // Rest advice
                if (health.rest_advice) {
                    $card.find('[data-element="rest-content"]').html('<p>' + this.escapeHtml(health.rest_advice) + '</p>');
                } else {
                    $card.find('[data-element="rest-card"]').hide();
                }

                // Lifestyle tips
                if (health.lifestyle_tips && health.lifestyle_tips.length > 0) {
                    let tipsHtml = '<ul>';
                    health.lifestyle_tips.forEach(function(tip) {
                        tipsHtml += '<li>' + self.escapeHtml(tip) + '</li>';
                    });
                    tipsHtml += '</ul>';
                    $card.find('[data-element="lifestyle-content"]').html(tipsHtml);
                } else {
                    $card.find('[data-element="lifestyle-card"]').hide();
                }
            } else {
                $card.find('[data-element="health"]').hide();
            }

            // Related blogs
            if (advice.related_blogs_data && advice.related_blogs_data.length > 0) {
                const $blogsList = $card.find('[data-element="blogs-list"]');
                $blogsList.empty();

                advice.related_blogs_data.forEach(function(blog) {
                    const blogHtml = '<a href="' + self.escapeHtml(blog.url) + '" class="bw-blogs__link" target="_blank" rel="noopener">' +
                        self.escapeHtml(blog.title) +
                        '</a>';
                    $blogsList.append(blogHtml);
                });

                $card.find('[data-element="blogs"]').removeClass('bw-hidden');
            }

            // Add card to messages
            this.elements.messages.append($card);
            this.scrollToBottomIfNeeded();
        },

        /**
         * Handle new question button
         */
        handleNewQuestion: function() {
            // Clear messages
            this.elements.messages.empty();

            // Reset conversation
            this.conversationHistory = [];
            this.sessionId = null;

            // Show welcome message
            if (prefersReducedMotion) {
                this.elements.welcome.show();
            } else {
                this.elements.welcome.fadeIn(200);
            }

            // Focus input
            this.elements.input.focus();

            // Scroll to top
            this.scrollToTop();
        },

        /**
         * Track product click
         */
        trackProductClick: function(productId) {
            $.ajax({
                url: bracefoxBW.ajax_url,
                type: 'POST',
                data: {
                    action: 'bw_track_click',
                    nonce: bracefoxBW.nonce,
                    session_id: this.sessionId,
                    product_id: productId
                }
            });
        },

        /**
         * Check if user is near bottom of chat
         */
        isNearBottom: function() {
            const chatBody = this.elements.chatBody[0];
            if (!chatBody) return true;

            const scrollTop = chatBody.scrollTop;
            const scrollHeight = chatBody.scrollHeight;
            const clientHeight = chatBody.clientHeight;

            return (scrollHeight - scrollTop - clientHeight) < this.scrollThreshold;
        },

        /**
         * Scroll to bottom only if user is near bottom
         */
        scrollToBottomIfNeeded: function() {
            if (!this.isNearBottom()) {
                return;
            }

            const chatBody = this.elements.chatBody[0];
            if (!chatBody) return;

            if (prefersReducedMotion) {
                chatBody.scrollTop = chatBody.scrollHeight;
            } else {
                // Use native smooth scroll (respects CSS scroll-behavior)
                chatBody.scrollTo({
                    top: chatBody.scrollHeight,
                    behavior: 'smooth'
                });
            }
        },

        /**
         * Scroll to top of chat
         */
        scrollToTop: function() {
            const chatBody = this.elements.chatBody[0];
            if (!chatBody) return;

            if (prefersReducedMotion) {
                chatBody.scrollTop = 0;
            } else {
                chatBody.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';

            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return text.toString().replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        BracefoxChat.init();
    });

})(jQuery);
