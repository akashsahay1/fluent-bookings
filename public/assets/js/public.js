/**
 * Public JavaScript
 */

(function($) {
    'use strict';

    var FluentBookingPublic = {
        availableDates: {},
        unavailableDates: {},
        currentMonth: new Date(),
        selectedDate: null,
        bookedSlots: {},

        init: function() {
            this.initForms();
            this.initCalendar();
            this.loadAvailability();
        },

        initForms: function() {
            var self = this;

            jQuery('.fluent-booking-form').on('submit', function(e) {
                e.preventDefault();
                self.submitForm(jQuery(this));
            });
        },

        initCalendar: function() {
            var self = this;

            // Initialize all calendar pickers
            jQuery('.fb-calendar-picker').each(function() {
                var calendar = jQuery(this);
                var formWrapper = calendar.closest('.fluent-booking-form-wrapper');
                var formId = formWrapper.data('form-id');

                self.renderCalendar(calendar, formId);

                // Previous month
                calendar.find('.fb-cal-prev').on('click', function() {
                    self.currentMonth.setMonth(self.currentMonth.getMonth() - 1);
                    self.renderCalendar(calendar, formId);
                });

                // Next month
                calendar.find('.fb-cal-next').on('click', function() {
                    self.currentMonth.setMonth(self.currentMonth.getMonth() + 1);
                    self.renderCalendar(calendar, formId);
                });
            });
        },

        renderCalendar: function(calendar, formId) {
            var self = this;
            var year = self.currentMonth.getFullYear();
            var month = self.currentMonth.getMonth();

            // Update header
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December'];
            calendar.find('.fb-cal-month-year').text(monthNames[month] + ' ' + year);

            // Render day headers
            var dayHeaders = '<div class="fb-cal-day-name">Sun</div><div class="fb-cal-day-name">Mon</div><div class="fb-cal-day-name">Tue</div><div class="fb-cal-day-name">Wed</div><div class="fb-cal-day-name">Thu</div><div class="fb-cal-day-name">Fri</div><div class="fb-cal-day-name">Sat</div>';
            calendar.find('.fb-cal-days-header').html(dayHeaders);

            // Render days
            var firstDay = new Date(year, month, 1);
            var lastDay = new Date(year, month + 1, 0);
            var startDay = firstDay.getDay();
            var daysInMonth = lastDay.getDate();

            var daysHTML = '';
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            // Empty cells before first day
            for (var i = 0; i < startDay; i++) {
                daysHTML += '<div class="fb-cal-day fb-cal-day-empty"></div>';
            }

            // Days of month
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(year, month, day);
                var dateStr = self.formatDate(date);
                var dayClass = 'fb-cal-day';

                // Check if today
                if (date.getTime() === today.getTime()) {
                    dayClass += ' fb-cal-today';
                }

                // Check if past
                if (date < today) {
                    dayClass += ' fb-cal-past';
                }

                // Check if available
                var isAvailable = self.availableDates[formId] && self.availableDates[formId].indexOf(dateStr) > -1;
                var isUnavailable = self.unavailableDates[formId] && self.unavailableDates[formId].indexOf(dateStr) > -1;

                if (date >= today) {
                    if (isAvailable) {
                        dayClass += ' fb-cal-available';
                    } else if (isUnavailable) {
                        dayClass += ' fb-cal-unavailable';
                    }
                }

                // Check if selected
                if (self.selectedDate === dateStr) {
                    dayClass += ' fb-cal-selected';
                }

                daysHTML += '<div class="' + dayClass + '" data-date="' + dateStr + '" data-day="' + day + '">' + day + '</div>';
            }

            calendar.find('.fb-cal-days-grid').html(daysHTML);

            // Add click handlers
            calendar.find('.fb-cal-available').on('click', function() {
                var selectedDate = jQuery(this).data('date');
                self.selectDate(calendar, formId, selectedDate);
            });
        },

        selectDate: function(calendar, formId, date) {
            var self = this;

            self.selectedDate = date;

            // Update hidden input
            var fieldId = calendar.data('field-id');
            jQuery('#' + fieldId).val(date);

            // Update visual selection
            calendar.find('.fb-cal-day').removeClass('fb-cal-selected');
            calendar.find('[data-date="' + date + '"]').addClass('fb-cal-selected');

            // Load time slots
            self.loadTimeSlots(formId, date);
        },

        loadTimeSlots: function(formId, date) {
            var self = this;
            var timeSlotsContainer = jQuery('.fb-time-slots-picker');
            var instruction = timeSlotsContainer.find('.fb-time-instruction');
            var grid = timeSlotsContainer.find('.fb-time-slots-grid');

            instruction.text('Loading available time slots...');
            grid.html('');

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
                        instruction.text('Select your preferred time:');
                        self.renderTimeSlots(timeSlotsContainer, response.data);
                    } else {
                        instruction.text('No available time slots for this date.');
                        grid.html('');
                    }
                },
                error: function() {
                    instruction.text('Error loading time slots. Please try again.');
                    grid.html('');
                }
            });
        },

        renderTimeSlots: function(container, slots) {
            var self = this;
            var fieldId = container.data('field-id');
            var grid = container.find('.fb-time-slots-grid');
            var html = '';

            jQuery.each(slots, function(index, slot) {
                var slotClass = 'fb-time-slot';
                var clickable = true;

                // Apply status-based classes
                if (slot.status === 'booked') {
                    slotClass += ' fb-time-slot-booked';
                    clickable = false;
                } else if (slot.status === 'blocked') {
                    slotClass += ' fb-time-slot-blocked';
                    clickable = false;
                }

                html += '<div class="' + slotClass + '" data-time="' + slot.value + '" data-status="' + slot.status + '">';
                html += '<span class="fb-time-label">' + slot.label + '</span>';
                html += '</div>';
            });

            grid.html(html);

            // Add click handlers only for available slots
            grid.find('.fb-time-slot:not(.fb-time-slot-booked):not(.fb-time-slot-blocked)').on('click', function() {
                var time = jQuery(this).data('time');

                // Update visual selection
                grid.find('.fb-time-slot').removeClass('fb-time-selected');
                jQuery(this).addClass('fb-time-selected');

                // Update hidden input
                jQuery('#' + fieldId).val(time);
            });
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

                            // Re-render calendar with availability data
                            var calendar = formWrapper.find('.fb-calendar-picker');
                            if (calendar.length) {
                                self.renderCalendar(calendar, formId);
                            }
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

                        // Reset visual selections
                        jQuery('.fb-cal-selected').removeClass('fb-cal-selected');
                        jQuery('.fb-time-selected').removeClass('fb-time-selected');
                        jQuery('.fb-time-instruction').text('Please select a date first');
                        jQuery('.fb-time-slots-grid').html('');

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
