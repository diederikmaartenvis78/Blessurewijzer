/**
 * Bracefox Blessurewijzer 2.0 - Frontend JavaScript
 * Handles chat interactions and UI updates
 */

(function($) {
    'use strict';

    /**
     * Main Chat Handler
     */
    const BracefoxChat = {
        // State
        sessionId: null,
        conversationHistory: [],
        isProcessing: false,
        loadingStartTime: null,
        loadingTimeout: null,

        // DOM elements
        $widget: null,
        $form: null,
        $input: null,
        $sendButton: null,
        $messages: null,
        $loading: null,
        $welcome: null,

        /**
         * Initialize the chat
         */
        init: function() {
            this.$widget = $('#bw-widget');
            this.$form = $('#bw-chat-form');
            this.$input = $('#bw-input');
            this.$sendButton = $('#bw-send-button');
            this.$messages = $('#bw-messages');
            this.$loading = $('#bw-loading');
            this.$welcome = $('#bw-welcome');

            this.bindEvents();
            this.autoResizeTextarea();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.handleSendMessage();
            });

            // New question button (delegated)
            this.$widget.on('click', '#bw-new-question', function(e) {
                e.preventDefault();
                self.handleNewQuestion();
            });

            // Product click tracking (delegated)
            this.$widget.on('click', '.bw-product-button', function(e) {
                const productId = $(this).data('product-id');
                if (productId) {
                    self.trackProductClick(productId);
                }
            });

            // Textarea auto-resize
            this.$input.on('input', function() {
                self.autoResizeTextarea();
            });

            // Enter to send (Shift+Enter for new line)
            this.$input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.$form.trigger('submit');
                }
            });
        },

        /**
         * Handle sending a message
         */
        handleSendMessage: function() {
            if (this.isProcessing) {
                return;
            }

            const message = this.$input.val().trim();

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

            // Clear input
            this.$input.val('').trigger('input');

            // Hide welcome message
            this.$welcome.fadeOut(300);

            // Send to server
            this.sendToAI(message);
        },

        /**
         * Send message to AI via AJAX
         */
        sendToAI: function(message) {
            const self = this;

            this.isProcessing = true;
            this.$sendButton.prop('disabled', true);
            this.showLoading();

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
                    self.$sendButton.prop('disabled', false);
                    self.hideLoading();
                }
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

            // Scroll to bottom
            this.scrollToBottom();
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
            const $message = $('<div class="bw-message bw-message-user">' +
                '<div class="bw-message-user-bubble">' + this.escapeHtml(message) + '</div>' +
                '</div>');

            this.$messages.append($message);
            this.scrollToBottom();
        },

        /**
         * Add assistant message to chat
         */
        addAssistantMessage: function(message) {
            const $message = $('<div class="bw-message bw-message-assistant">' +
                '<div class="bw-message-assistant-bubble">' + this.escapeHtml(message) + '</div>' +
                '</div>');

            this.$messages.append($message);
            this.scrollToBottom();
        },

        /**
         * Render full advice card
         */
        renderAdviceCard: function(advice) {
            const $template = $('#bw-advice-template');
            const $card = $($template.html()).clone();

            // Personal message
            if (advice.personal_message) {
                this.addAssistantMessage(advice.personal_message);
            }

            // Severity warning
            if (advice.severity_warning) {
                $card.find('.bw-severity-warning').show();
            }

            // Product recommendation
            if (advice.product_recommendation && advice.product_recommendation.product_data) {
                const product = advice.product_recommendation.product_data;
                const reasoning = advice.product_recommendation.reasoning;

                $card.find('.bw-product-image img').attr({
                    src: product.image,
                    alt: product.name
                });
                $card.find('.bw-product-name').text(product.name);
                $card.find('.bw-product-reasoning').text(reasoning);
                $card.find('.bw-product-price').text('€' + product.price.toFixed(2));
                $card.find('.bw-product-button')
                    .attr('href', product.url)
                    .data('product-id', product.id);
            } else {
                $card.find('.bw-product-section').hide();
            }

            // Health advice
            if (advice.health_advice) {
                const health = advice.health_advice;

                // Exercises
                if (health.exercises && health.exercises.length > 0) {
                    const $exercisesList = $card.find('.bw-exercises-list');
                    $exercisesList.empty();

                    health.exercises.forEach(function(exercise) {
                        const $exercise = $('<div class="bw-exercise">' +
                            '<div class="bw-exercise-name">' + BracefoxChat.escapeHtml(exercise.name) + '</div>' +
                            '<div class="bw-exercise-description">' + BracefoxChat.escapeHtml(exercise.description) + '</div>' +
                            '<div class="bw-exercise-meta">' +
                            BracefoxChat.escapeHtml(exercise.duration) + ' • ' +
                            BracefoxChat.escapeHtml(exercise.frequency) +
                            '</div>' +
                            '</div>');
                        $exercisesList.append($exercise);
                    });
                }

                // Thermal advice
                if (health.thermal_advice) {
                    const $thermalContent = $card.find('.bw-thermal-content');
                    $thermalContent.html(
                        '<p><strong>' + this.escapeHtml(health.thermal_advice.method === 'koelen' ? '❄️ Koelen' : '🔥 Verwarmen') + '</strong></p>' +
                        '<p>' + this.escapeHtml(health.thermal_advice.explanation) + '</p>' +
                        '<p><em>' + this.escapeHtml(health.thermal_advice.duration) + '</em></p>'
                    );
                }

                // Rest advice
                if (health.rest_advice) {
                    const $restContent = $card.find('.bw-rest-content');
                    $restContent.html('<p>' + this.escapeHtml(health.rest_advice) + '</p>');
                }

                // Lifestyle tips
                if (health.lifestyle_tips && health.lifestyle_tips.length > 0) {
                    const $lifestyleContent = $card.find('.bw-lifestyle-content');
                    let tipsHtml = '<ul>';
                    health.lifestyle_tips.forEach(function(tip) {
                        tipsHtml += '<li>' + BracefoxChat.escapeHtml(tip) + '</li>';
                    });
                    tipsHtml += '</ul>';
                    $lifestyleContent.html(tipsHtml);
                } else {
                    $card.find('.bw-health-card').eq(3).hide();
                }
            }

            // Related blogs
            if (advice.related_blogs_data && advice.related_blogs_data.length > 0) {
                const $blogsList = $card.find('.bw-blogs-list');
                $blogsList.empty();

                advice.related_blogs_data.forEach(function(blog) {
                    const $blog = $('<div>' +
                        '• <a href="' + blog.url + '" class="bw-blog-link" target="_blank">' +
                        BracefoxChat.escapeHtml(blog.title) +
                        '</a>' +
                        '</div>');
                    $blogsList.append($blog);
                });

                $card.find('.bw-blogs-section').show();
            }

            // Add card to messages
            this.$messages.append($card);
            this.scrollToBottom();
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            this.loadingStartTime = Date.now();
            this.$loading.show();
            $('#bw-loading-simple').show();
            $('#bw-loading-premium').hide();

            // Switch to premium loading after 2 seconds
            const self = this;
            this.loadingTimeout = setTimeout(function() {
                self.showPremiumLoading();
            }, 2000);
        },

        /**
         * Show premium loading animation
         */
        showPremiumLoading: function() {
            $('#bw-loading-simple').hide();
            $('#bw-loading-premium').show();

            // Animate steps
            this.animateLoadingStep(1, 0);
            this.animateLoadingStep(2, 1000);
            this.animateLoadingStep(3, 2000);
        },

        /**
         * Animate a loading step
         */
        animateLoadingStep: function(stepNumber, delay) {
            setTimeout(function() {
                const $step = $('.bw-loading-step[data-step="' + stepNumber + '"]');
                $step.addClass('active');

                setTimeout(function() {
                    $step.removeClass('active').addClass('completed');
                }, 800);
            }, delay);
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
            }

            this.$loading.hide();
            $('#bw-loading-simple').show();
            $('#bw-loading-premium').hide();

            // Reset loading steps
            $('.bw-loading-step').removeClass('active completed');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $error = $('<div class="bw-message bw-message-assistant">' +
                '<div class="bw-message-assistant-bubble" style="background: #fee; border-color: #fcc; color: #c00;">' +
                '⚠️ ' + this.escapeHtml(message) +
                '</div>' +
                '</div>');

            this.$messages.append($error);
            this.scrollToBottom();
        },

        /**
         * Handle new question button
         */
        handleNewQuestion: function() {
            // Clear messages
            this.$messages.empty();

            // Reset conversation
            this.conversationHistory = [];
            this.sessionId = null;

            // Show welcome message
            this.$welcome.fadeIn(300);

            // Focus input
            this.$input.focus();

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
         * Auto-resize textarea
         */
        autoResizeTextarea: function() {
            const $textarea = this.$input;
            $textarea.css('height', 'auto');
            const scrollHeight = $textarea[0].scrollHeight;
            $textarea.css('height', Math.min(scrollHeight, 120) + 'px');
        },

        /**
         * Scroll messages to bottom
         */
        scrollToBottom: function() {
            const $messagesContainer = this.$messages;
            setTimeout(function() {
                $messagesContainer.animate({
                    scrollTop: $messagesContainer[0].scrollHeight
                }, 300);
            }, 100);
        },

        /**
         * Scroll messages to top
         */
        scrollToTop: function() {
            this.$messages.animate({ scrollTop: 0 }, 300);
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
        if ($('#bw-widget').length > 0) {
            BracefoxChat.init();
        }
    });

})(jQuery);
