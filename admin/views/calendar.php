<?php
/**
 * Calendar View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all forms for filter
$forms = $wpdb->get_results(
    "SELECT id, title FROM {$wpdb->prefix}fluentbooking_forms WHERE status = 'active' ORDER BY title ASC",
    ARRAY_A
);
?>

<div class="wrap fluent-booking-calendar">
    <h1><?php esc_html_e('Booking Calendar', 'fluent-booking'); ?></h1>

    <div class="fb-calendar-header">
        <div class="fb-calendar-controls">
            <button type="button" id="fb-calendar-prev" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Previous', 'fluent-booking'); ?>
            </button>

            <button type="button" id="fb-calendar-today" class="button">
                <?php esc_html_e('Today', 'fluent-booking'); ?>
            </button>

            <button type="button" id="fb-calendar-next" class="button">
                <?php esc_html_e('Next', 'fluent-booking'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>

        <div class="fb-calendar-title">
            <h2 id="fb-calendar-month-year"></h2>
        </div>

        <div class="fb-calendar-filters">
            <select id="fb-calendar-form-filter">
                <option value=""><?php esc_html_e('All Forms', 'fluent-booking'); ?></option>
                <?php foreach ($forms as $form) : ?>
                    <option value="<?php echo esc_attr($form['id']); ?>"><?php echo esc_html($form['title']); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="fb-calendar-view">
                <option value="month"><?php esc_html_e('Month View', 'fluent-booking'); ?></option>
                <option value="week"><?php esc_html_e('Week View', 'fluent-booking'); ?></option>
                <option value="day"><?php esc_html_e('Day View', 'fluent-booking'); ?></option>
            </select>
        </div>
    </div>

    <div class="fb-calendar-legend">
        <span class="fb-legend-item">
            <span class="fb-legend-color fb-status-pending"></span>
            <?php esc_html_e('Pending', 'fluent-booking'); ?>
        </span>
        <span class="fb-legend-item">
            <span class="fb-legend-color fb-status-confirmed"></span>
            <?php esc_html_e('Confirmed', 'fluent-booking'); ?>
        </span>
        <span class="fb-legend-item">
            <span class="fb-legend-color fb-status-completed"></span>
            <?php esc_html_e('Completed', 'fluent-booking'); ?>
        </span>
        <span class="fb-legend-item">
            <span class="fb-legend-color fb-status-cancelled"></span>
            <?php esc_html_e('Cancelled', 'fluent-booking'); ?>
        </span>
    </div>

    <!-- Calendar Container -->
    <div id="fb-calendar-container" class="fb-calendar-month-view"></div>
</div>

<!-- Booking Details Modal -->
<div id="fb-booking-details-modal" class="fb-modal" style="display: none;">
    <div class="fb-modal-content">
        <span class="fb-modal-close">&times;</span>
        <h2><?php esc_html_e('Booking Details', 'fluent-booking'); ?></h2>
        <div id="fb-booking-details-content"></div>

        <div class="fb-modal-actions">
            <button type="button" class="button button-primary" id="fb-update-booking-modal">
                <?php esc_html_e('Update Status', 'fluent-booking'); ?>
            </button>
            <button type="button" class="button" id="fb-close-modal">
                <?php esc_html_e('Close', 'fluent-booking'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentDate = new Date();
    var currentView = 'month';
    var selectedFormId = '';

    // Initialize calendar
    function initCalendar() {
        renderCalendar();
        loadBookings();
    }

    // Render calendar structure
    function renderCalendar() {
        var container = jQuery('#fb-calendar-container');
        var monthYear = jQuery('#fb-calendar-month-year');

        if (currentView === 'month') {
            renderMonthView(container, monthYear);
        } else if (currentView === 'week') {
            renderWeekView(container, monthYear);
        } else if (currentView === 'day') {
            renderDayView(container, monthYear);
        }
    }

    // Render month view
    function renderMonthView(container, monthYear) {
        var year = currentDate.getFullYear();
        var month = currentDate.getMonth();

        monthYear.text(getMonthName(month) + ' ' + year);

        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var startDay = firstDay.getDay();
        var daysInMonth = lastDay.getDate();

        var html = '<div class="fb-calendar-grid">';

        // Day headers
        var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        html += '<div class="fb-calendar-row fb-calendar-header-row">';
        jQuery.each(dayNames, function(index, day) {
            html += '<div class="fb-calendar-day-header">' + day + '</div>';
        });
        html += '</div>';

        // Empty cells before first day
        html += '<div class="fb-calendar-row">';
        for (var i = 0; i < startDay; i++) {
            html += '<div class="fb-calendar-day fb-calendar-day-empty"></div>';
        }

        // Days of month
        var dayCount = startDay;
        for (var day = 1; day <= daysInMonth; day++) {
            if (dayCount % 7 === 0 && day > 1) {
                html += '</div><div class="fb-calendar-row">';
            }

            var dateStr = year + '-' + pad(month + 1) + '-' + pad(day);
            var isToday = isDateToday(year, month, day);
            var dayClass = 'fb-calendar-day';
            if (isToday) dayClass += ' fb-calendar-today';

            html += '<div class="' + dayClass + '" data-date="' + dateStr + '">';
            html += '<div class="fb-calendar-day-number">' + day + '</div>';
            html += '<div class="fb-calendar-day-bookings" data-date="' + dateStr + '"></div>';
            html += '</div>';

            dayCount++;
        }

        // Empty cells after last day
        while (dayCount % 7 !== 0) {
            html += '<div class="fb-calendar-day fb-calendar-day-empty"></div>';
            dayCount++;
        }

        html += '</div></div>';

        container.html(html);
    }

    // Render week view
    function renderWeekView(container, monthYear) {
        var startOfWeek = getStartOfWeek(currentDate);
        var endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(endOfWeek.getDate() + 6);

        monthYear.text(formatDate(startOfWeek) + ' - ' + formatDate(endOfWeek));

        var html = '<div class="fb-calendar-week-grid">';

        // Time slots (7 AM - 9 PM)
        var startHour = 7;
        var endHour = 21;

        // Header with days
        html += '<div class="fb-calendar-week-header">';
        html += '<div class="fb-calendar-time-header">Time</div>';

        for (var i = 0; i < 7; i++) {
            var date = new Date(startOfWeek);
            date.setDate(date.getDate() + i);
            var dateStr = formatDateShort(date);
            var isToday = isDateToday(date.getFullYear(), date.getMonth(), date.getDate());

            html += '<div class="fb-calendar-week-day-header' + (isToday ? ' fb-calendar-today' : '') + '" data-date="' + getDateString(date) + '">';
            html += dateStr;
            html += '</div>';
        }
        html += '</div>';

        // Time slots
        for (var hour = startHour; hour < endHour; hour++) {
            html += '<div class="fb-calendar-week-row">';
            html += '<div class="fb-calendar-time-slot">' + formatHour(hour) + '</div>';

            for (var i = 0; i < 7; i++) {
                var date = new Date(startOfWeek);
                date.setDate(date.getDate() + i);
                var dateStr = getDateString(date);

                html += '<div class="fb-calendar-week-cell" data-date="' + dateStr + '" data-hour="' + hour + '"></div>';
            }

            html += '</div>';
        }

        html += '</div>';

        container.html(html);
    }

    // Render day view
    function renderDayView(container, monthYear) {
        var year = currentDate.getFullYear();
        var month = currentDate.getMonth();
        var day = currentDate.getDate();

        monthYear.text(formatDate(currentDate));

        var html = '<div class="fb-calendar-day-grid">';

        // Time slots (7 AM - 9 PM)
        var startHour = 7;
        var endHour = 21;

        for (var hour = startHour; hour < endHour; hour++) {
            var dateStr = getDateString(currentDate);

            html += '<div class="fb-calendar-day-row">';
            html += '<div class="fb-calendar-time-slot">' + formatHour(hour) + '</div>';
            html += '<div class="fb-calendar-day-cell" data-date="' + dateStr + '" data-hour="' + hour + '"></div>';
            html += '</div>';
        }

        html += '</div>';

        container.html(html);
    }

    // Load bookings
    function loadBookings() {
        var year = currentDate.getFullYear();
        var month = currentDate.getMonth() + 1;

        var dateFrom, dateTo;

        if (currentView === 'month') {
            dateFrom = year + '-' + pad(month) + '-01';
            dateTo = year + '-' + pad(month) + '-' + new Date(year, month, 0).getDate();
        } else if (currentView === 'week') {
            var startOfWeek = getStartOfWeek(currentDate);
            var endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(endOfWeek.getDate() + 6);

            dateFrom = getDateString(startOfWeek);
            dateTo = getDateString(endOfWeek);
        } else {
            dateFrom = dateTo = getDateString(currentDate);
        }

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_get_calendar_bookings',
                month: month,
                year: year,
                form_id: selectedFormId,
                date_from: dateFrom,
                date_to: dateTo,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayBookings(response.data);
                }
            }
        });
    }

    // Display bookings on calendar
    function displayBookings(bookings) {
        // Clear existing bookings
        jQuery('.fb-calendar-day-bookings').empty();
        jQuery('.fb-calendar-week-cell, .fb-calendar-day-cell').empty();

        if (!bookings || bookings.length === 0) {
            return;
        }

        jQuery.each(bookings, function(index, booking) {
            var date = booking.start.split('T')[0];
            var time = booking.start.split('T')[1];
            var hour = parseInt(time.split(':')[0]);

            if (currentView === 'month') {
                var dayEl = jQuery('.fb-calendar-day-bookings[data-date="' + date + '"]');
                if (dayEl.length) {
                    var bookingEl = jQuery('<div class="fb-calendar-booking fb-status-' + booking.status + '" data-booking-id="' + booking.id + '"></div>');
                    bookingEl.text(time.substring(0, 5) + ' - ' + booking.title);
                    bookingEl.on('click', function(e) {
                        e.stopPropagation();
                        showBookingDetails(booking.id);
                    });
                    dayEl.append(bookingEl);
                }
            } else if (currentView === 'week') {
                var cellEl = jQuery('.fb-calendar-week-cell[data-date="' + date + '"][data-hour="' + hour + '"]');
                if (cellEl.length) {
                    var bookingEl = jQuery('<div class="fb-calendar-booking fb-status-' + booking.status + '" data-booking-id="' + booking.id + '"></div>');
                    bookingEl.html('<strong>' + booking.title + '</strong><br>' + time.substring(0, 5));
                    bookingEl.on('click', function(e) {
                        e.stopPropagation();
                        showBookingDetails(booking.id);
                    });
                    cellEl.append(bookingEl);
                }
            } else {
                var cellEl = jQuery('.fb-calendar-day-cell[data-date="' + date + '"][data-hour="' + hour + '"]');
                if (cellEl.length) {
                    var bookingEl = jQuery('<div class="fb-calendar-booking fb-status-' + booking.status + '" data-booking-id="' + booking.id + '"></div>');
                    bookingEl.html('<strong>' + booking.title + '</strong><br>' + booking.email + '<br>' + time.substring(0, 5));
                    bookingEl.on('click', function(e) {
                        e.stopPropagation();
                        showBookingDetails(booking.id);
                    });
                    cellEl.append(bookingEl);
                }
            }
        });
    }

    // Show booking details
    function showBookingDetails(bookingId) {
        // Load booking details via AJAX
        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_get_booking_details',
                booking_id: bookingId,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderBookingDetails(response.data);
                    jQuery('#fb-booking-details-modal').show();
                }
            }
        });
    }

    // Render booking details
    function renderBookingDetails(booking) {
        var html = '<div class="fb-booking-detail-row"><strong>Customer:</strong> ' + booking.customer_name + '</div>';
        html += '<div class="fb-booking-detail-row"><strong>Email:</strong> ' + booking.customer_email + '</div>';
        html += '<div class="fb-booking-detail-row"><strong>Phone:</strong> ' + (booking.customer_phone || 'N/A') + '</div>';
        html += '<div class="fb-booking-detail-row"><strong>Date:</strong> ' + booking.booking_date + '</div>';
        html += '<div class="fb-booking-detail-row"><strong>Time:</strong> ' + booking.booking_time + '</div>';
        html += '<div class="fb-booking-detail-row"><strong>Duration:</strong> ' + booking.duration + ' minutes</div>';
        html += '<div class="fb-booking-detail-row">';
        html += '<strong>Status:</strong> ';
        html += '<select id="fb-booking-status-update" data-booking-id="' + booking.id + '">';
        html += '<option value="pending"' + (booking.status === 'pending' ? ' selected' : '') + '>Pending</option>';
        html += '<option value="confirmed"' + (booking.status === 'confirmed' ? ' selected' : '') + '>Confirmed</option>';
        html += '<option value="cancelled"' + (booking.status === 'cancelled' ? ' selected' : '') + '>Cancelled</option>';
        html += '<option value="completed"' + (booking.status === 'completed' ? ' selected' : '') + '>Completed</option>';
        html += '</select>';
        html += '</div>';

        if (booking.customer_notes) {
            html += '<div class="fb-booking-detail-row"><strong>Notes:</strong><br>' + booking.customer_notes + '</div>';
        }

        jQuery('#fb-booking-details-content').html(html);
    }

    // Helper functions
    function getMonthName(month) {
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        return months[month];
    }

    function pad(num) {
        return num < 10 ? '0' + num : num;
    }

    function isDateToday(year, month, day) {
        var today = new Date();
        return year === today.getFullYear() && month === today.getMonth() && day === today.getDate();
    }

    function getStartOfWeek(date) {
        var d = new Date(date);
        var day = d.getDay();
        var diff = d.getDate() - day;
        return new Date(d.setDate(diff));
    }

    function formatDate(date) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
    }

    function formatDateShort(date) {
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return days[date.getDay()] + ' ' + (date.getMonth() + 1) + '/' + date.getDate();
    }

    function getDateString(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function formatHour(hour) {
        var ampm = hour >= 12 ? 'PM' : 'AM';
        var h = hour % 12;
        h = h ? h : 12;
        return h + ':00 ' + ampm;
    }

    // Event handlers
    jQuery('#fb-calendar-prev').on('click', function() {
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() - 1);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() - 7);
        } else {
            currentDate.setDate(currentDate.getDate() - 1);
        }
        initCalendar();
    });

    jQuery('#fb-calendar-next').on('click', function() {
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + 1);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + 7);
        } else {
            currentDate.setDate(currentDate.getDate() + 1);
        }
        initCalendar();
    });

    jQuery('#fb-calendar-today').on('click', function() {
        currentDate = new Date();
        initCalendar();
    });

    jQuery('#fb-calendar-view').on('change', function() {
        currentView = jQuery(this).val();
        initCalendar();
    });

    jQuery('#fb-calendar-form-filter').on('change', function() {
        selectedFormId = jQuery(this).val();
        loadBookings();
    });

    // Update booking status
    jQuery('#fb-update-booking-modal').on('click', function() {
        var bookingId = jQuery('#fb-booking-status-update').data('booking-id');
        var newStatus = jQuery('#fb-booking-status-update').val();

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_update_booking_status',
                booking_id: bookingId,
                status: newStatus,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    jQuery('#fb-booking-details-modal').hide();
                    loadBookings();
                }
            }
        });
    });

    // Close modal
    jQuery('.fb-modal-close, #fb-close-modal').on('click', function() {
        jQuery('#fb-booking-details-modal').hide();
    });

    jQuery('#fb-booking-details-modal').on('click', function(e) {
        if (e.target === this) {
            jQuery(this).hide();
        }
    });

    // Initialize
    initCalendar();
});
</script>
