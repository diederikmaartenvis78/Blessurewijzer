/**
 * Bracefox Blessurewijzer 2.0 - Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Admin functionality
     */
    const BracefoxAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Events are handled in the settings-page.php template
            // This file is for future admin functionality
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        BracefoxAdmin.init();
    });

})(jQuery);
