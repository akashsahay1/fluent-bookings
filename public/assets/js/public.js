/**
 * Public JavaScript
 */

(function($) {
    'use strict';

    var FluentBookingPublic = {
        availableDates: {},
        unavailableDates: {},

        init: function() {
            this.initForms();
            this.initDatePicker();
            this.initTimePicker();
            this.loadAvailability();
        },

        loadAvailability: function() {
            var self = this;

            jQuery('.fluent-booking-form-wrapper').each(function() {
                var formWrapper = jQuery(this);
                var formId = formWrapper.data('form-id');

                // Load availability for next 60 days
                var startDate = new Date();
                var endDate = new Date();
                endDate.setDate(endDate.getDate() + 60);

                jQuery.ajax({
                    url: fluentBookingPublic.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fb_check_date_availability',
                        form_id: formId,
                        start_date: self.formatDate(startDate),
                        end_date: self.formatDate(endDate)
                    },
                    success: function(response) {
                        if (response.success) {
                            self.availableDates[formId] = response.data.available;
                            self.unavailableDates[formId] = response.data.unavailable;
                            self.highlightDates(formWrapper, formId);
                        }
                    }
                });
            });
        },

        formatDate: function(date) {
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        },

        highlightDates: function(formWrapper, formId) {
            var self = this;
            var dateField = formWrapper.find('.fb-date-field');

            if (dateField.length) {
                // Add custom styling via CSS class
                dateField.addClass('fb-date-with-availability');

                // Disable unavailable dates
                if (self.unavailableDates[formId]) {
                    var minDate = new Date().toISOString().split('T')[0];
                    var disabledDates = self.unavailableDates[formId];

                    // Update the date input attributes
                    dateField.attr('data-disabled-dates', disabledDates.join(','));
                }
            }
        },

        initForms: function() {
            var self = this;

            jQuery('.fluent-booking-form').on('submit', function(e) {
                e.preventDefault();
                self.submitForm(jQuery(this));
            });
        },

        initDatePicker: function() {
            var self = this;

            jQuery(document).on('change', '.fb-date-field', function() {
                var dateField = jQuery(this);
                var formWrapper = dateField.closest('.fluent-booking-form-wrapper');
                var formId = formWrapper.data('form-id');
                var selectedDate = dateField.val();

                // Load available time slots
                self.loadTimeSlots(formId, selectedDate, formWrapper);
            });
        },

        initTimePicker: function() {
            // Time picker is populated dynamically based on date selection
        },

        loadTimeSlots: function(formId, date, formWrapper) {
            var timeField = formWrapper.find('.fb-time-field');

            if (!date) {
                timeField.prop('disabled', true).html('<option value="">' + fluentBookingPublic.strings.select_date + '</option>');
                return;
            }

            timeField.prop('disabled', true).html('<option value="">' + fluentBookingPublic.strings.loading + '</option>');

            jQuery.ajax({
                url: fluentBookingPublic.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fb_get_available_slots',
                    form_id: formId,
                    date: date,
                    nonce: fluentBookingPublic.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">' + fluentBookingPublic.strings.select_time + '</option>';

                        jQuery.each(response.data, function(index, slot) {
                            options += '<option value="' + slot.value + '">' + slot.label + '</option>';
                        });

                        timeField.prop('disabled', false).html(options);
                    } else {
                        timeField.prop('disabled', true).html('<option value="">No available slots</option>');
                    }
                },
                error: function() {
                    timeField.prop('disabled', true).html('<option value="">Error loading slots</option>');
                }
            });
        },

        submitForm: function(form) {
            var formWrapper = form.closest('.fluent-booking-form-wrapper');
            var submitButton = form.find('.fb-submit-button');
            var messageDiv = form.find('.fb-form-message');

            // Validate required fields
            var isValid = true;
            form.find('[required]').each(function() {
                if (!jQuery(this).val()) {
                    isValid = false;
                    jQuery(this).addClass('fb-field-error');
                } else {
                    jQuery(this).removeClass('fb-field-error');
                }
            });

            if (!isValid) {
                messageDiv.removeClass('fb-success').addClass('fb-error').html(fluentBookingPublic.strings.fill_required).show();
                return;
            }

            // Disable submit button
            submitButton.prop('disabled', true).text('Submitting...');
            messageDiv.hide();

            // Submit form
            jQuery.ajax({
                url: fluentBookingPublic.ajaxurl,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        messageDiv.removeClass('fb-error').addClass('fb-success').html(response.data.message).show();
                        form[0].reset();

                        // Check for redirect
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        messageDiv.removeClass('fb-success').addClass('fb-error').html(response.data.message).show();
                        submitButton.prop('disabled', false).text('Book Appointment');
                    }
                },
                error: function() {
                    messageDiv.removeClass('fb-success').addClass('fb-error').html('An error occurred. Please try again.').show();
                    submitButton.prop('disabled', false).text('Book Appointment');
                }
            });
        }
    };

    // Initialize on document ready
    jQuery(document).ready(function() {
        FluentBookingPublic.init();
    });

})(jQuery);
