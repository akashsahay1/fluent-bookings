/**
 * Admin JavaScript
 */

(function($) {
    'use strict';

    var FluentBookingAdmin = {
        init: function() {
            this.initColorPickers();
        },

        initColorPickers: function() {
            // Simple color picker initialization
            if (jQuery('.fb-color-picker').length) {
                jQuery('.fb-color-picker').each(function() {
                    var input = jQuery(this);
                    input.attr('type', 'color');
                });
            }
        }
    };

    // Initialize on document ready
    jQuery(document).ready(function() {
        FluentBookingAdmin.init();
    });

})(jQuery);
