<?php
/**
 * Bookings List View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all forms for filter
$forms = $wpdb->get_results(
    "SELECT id, title FROM {$wpdb->prefix}fluentbooking_forms ORDER BY title ASC",
    ARRAY_A
);
?>

<div class="wrap fluent-booking-bookings-list">
    <h1><?php esc_html_e('All Bookings', 'fluent-bookings'); ?></h1>

    <!-- Filters -->
    <div class="fb-bookings-filters">
        <select id="fb-filter-form">
            <option value=""><?php esc_html_e('All Forms', 'fluent-bookings'); ?></option>
            <?php foreach ($forms as $form) : ?>
                <option value="<?php echo esc_attr($form['id']); ?>"><?php echo esc_html($form['title']); ?></option>
            <?php endforeach; ?>
        </select>

        <select id="fb-filter-status">
            <option value=""><?php esc_html_e('All Statuses', 'fluent-bookings'); ?></option>
            <option value="pending"><?php esc_html_e('Pending', 'fluent-bookings'); ?></option>
            <option value="confirmed"><?php esc_html_e('Confirmed', 'fluent-bookings'); ?></option>
            <option value="cancelled"><?php esc_html_e('Cancelled', 'fluent-bookings'); ?></option>
            <option value="completed"><?php esc_html_e('Completed', 'fluent-bookings'); ?></option>
        </select>

        <input type="date" id="fb-filter-date-from" placeholder="<?php esc_attr_e('From Date', 'fluent-bookings'); ?>">
        <input type="date" id="fb-filter-date-to" placeholder="<?php esc_attr_e('To Date', 'fluent-bookings'); ?>">

        <input type="text" id="fb-filter-search" placeholder="<?php esc_attr_e('Search customer...', 'fluent-bookings'); ?>">

        <button type="button" id="fb-apply-filters" class="button"><?php esc_html_e('Apply Filters', 'fluent-bookings'); ?></button>
        <button type="button" id="fb-reset-filters" class="button"><?php esc_html_e('Reset', 'fluent-bookings'); ?></button>
        <button type="button" id="fb-export-bookings" class="button"><?php esc_html_e('Export CSV', 'fluent-bookings'); ?></button>
    </div>

    <!-- Bookings Table -->
    <div id="fb-bookings-table-wrapper">
        <table class="wp-list-table widefat fixed striped" id="fb-bookings-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Customer', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Contact', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Date', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Time', 'fluent-bookings'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Status', 'fluent-bookings'); ?></th>
                    <th style="width: 200px;"><?php esc_html_e('Actions', 'fluent-bookings'); ?></th>
                </tr>
            </thead>
            <tbody id="fb-bookings-tbody">
                <tr>
                    <td colspan="7" class="fb-loading"><?php esc_html_e('Loading bookings...', 'fluent-bookings'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="fb-pagination" id="fb-pagination"></div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="fb-booking-modal" class="fb-modal" style="display: none;">
    <div class="fb-modal-content">
        <span class="fb-modal-close">&times;</span>
        <h2><?php esc_html_e('Booking Details', 'fluent-bookings'); ?></h2>
        <div id="fb-booking-details"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentPage = 1;
    var itemsPerPage = 20;

    // Load bookings
    function loadBookings() {
        var data = {
            action: 'fb_get_bookings',
            nonce: fluentBookingAdmin.nonce,
            form_id: jQuery('#fb-filter-form').val(),
            status: jQuery('#fb-filter-status').val(),
            date_from: jQuery('#fb-filter-date-from').val(),
            date_to: jQuery('#fb-filter-date-to').val(),
            search: jQuery('#fb-filter-search').val(),
            limit: itemsPerPage,
            offset: (currentPage - 1) * itemsPerPage
        };

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    renderBookings(response.data.bookings);
                    renderPagination(response.data.total);
                } else {
                    jQuery('#fb-bookings-tbody').html('<tr><td colspan="7">Error loading bookings</td></tr>');
                }
            }
        });
    }

    // Render bookings
    function renderBookings(bookings) {
        if (!bookings || bookings.length === 0) {
            jQuery('#fb-bookings-tbody').html('<tr><td colspan="7"><?php esc_html_e('No bookings found', 'fluent-bookings'); ?></td></tr>');
            return;
        }

        var html = '';
        jQuery.each(bookings, function(index, booking) {
            var statusClass = 'fb-status-' + booking.status;
            html += '<tr>';
            html += '<td>' + booking.id + '</td>';
            html += '<td><strong>' + booking.customer_name + '</strong></td>';
            html += '<td>' + booking.customer_email;
            if (booking.customer_phone) {
                html += '<br><small>' + booking.customer_phone + '</small>';
            }
            html += '</td>';
            html += '<td>' + formatDate(booking.booking_date) + '</td>';
            html += '<td>' + formatTime(booking.booking_time) + '</td>';
            html += '<td><select class="fb-status-select" data-booking-id="' + booking.id + '">';
            html += '<option value="pending"' + (booking.status === 'pending' ? ' selected' : '') + '><?php esc_html_e('Pending', 'fluent-bookings'); ?></option>';
            html += '<option value="confirmed"' + (booking.status === 'confirmed' ? ' selected' : '') + '><?php esc_html_e('Confirmed', 'fluent-bookings'); ?></option>';
            html += '<option value="cancelled"' + (booking.status === 'cancelled' ? ' selected' : '') + '><?php esc_html_e('Cancelled', 'fluent-bookings'); ?></option>';
            html += '<option value="completed"' + (booking.status === 'completed' ? ' selected' : '') + '><?php esc_html_e('Completed', 'fluent-bookings'); ?></option>';
            html += '</select></td>';
            html += '<td>';
            html += '<button class="button button-small fb-view-booking" data-booking-id="' + booking.id + '"><?php esc_html_e('View', 'fluent-bookings'); ?></button> ';
            html += '<button class="button button-small fb-delete-booking" data-booking-id="' + booking.id + '"><?php esc_html_e('Delete', 'fluent-bookings'); ?></button>';
            html += '</td>';
            html += '</tr>';
        });

        jQuery('#fb-bookings-tbody').html(html);
    }

    // Render pagination
    function renderPagination(total) {
        var totalPages = Math.ceil(total / itemsPerPage);

        if (totalPages <= 1) {
            jQuery('#fb-pagination').html('');
            return;
        }

        var html = '<div class="fb-pagination-info">Page ' + currentPage + ' of ' + totalPages + '</div>';
        html += '<div class="fb-pagination-buttons">';

        if (currentPage > 1) {
            html += '<button class="button fb-page-btn" data-page="1">First</button>';
            html += '<button class="button fb-page-btn" data-page="' + (currentPage - 1) + '">Previous</button>';
        }

        if (currentPage < totalPages) {
            html += '<button class="button fb-page-btn" data-page="' + (currentPage + 1) + '">Next</button>';
            html += '<button class="button fb-page-btn" data-page="' + totalPages + '">Last</button>';
        }

        html += '</div>';

        jQuery('#fb-pagination').html(html);
    }

    // Format date
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Format time
    function formatTime(timeString) {
        var time = new Date('2000-01-01 ' + timeString);
        return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    // Apply filters
    jQuery('#fb-apply-filters').on('click', function() {
        currentPage = 1;
        loadBookings();
    });

    // Reset filters
    jQuery('#fb-reset-filters').on('click', function() {
        jQuery('#fb-filter-form, #fb-filter-status, #fb-filter-date-from, #fb-filter-date-to, #fb-filter-search').val('');
        currentPage = 1;
        loadBookings();
    });

    // Pagination
    jQuery(document).on('click', '.fb-page-btn', function() {
        currentPage = parseInt(jQuery(this).data('page'));
        loadBookings();
    });

    // Update booking status
    jQuery(document).on('change', '.fb-status-select', function() {
        var bookingId = jQuery(this).data('booking-id');
        var newStatus = jQuery(this).val();

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
                    // Show success message
                } else {
                    alert(response.data.message || 'Error updating status');
                    loadBookings();
                }
            }
        });
    });

    // Delete booking
    jQuery(document).on('click', '.fb-delete-booking', function() {
        if (!confirm(fluentBookingAdmin.strings.confirm_delete)) {
            return;
        }

        var bookingId = jQuery(this).data('booking-id');

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_delete_booking',
                booking_id: bookingId,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    loadBookings();
                } else {
                    alert(response.data.message || 'Error deleting booking');
                }
            }
        });
    });

    // Export bookings
    jQuery('#fb-export-bookings').on('click', function() {
        var params = new URLSearchParams({
            action: 'fb_export_bookings',
            nonce: fluentBookingAdmin.nonce,
            form_id: jQuery('#fb-filter-form').val(),
            status: jQuery('#fb-filter-status').val(),
            date_from: jQuery('#fb-filter-date-from').val(),
            date_to: jQuery('#fb-filter-date-to').val()
        });

        window.location.href = fluentBookingAdmin.ajaxurl + '?' + params.toString();
    });

    // Initial load
    loadBookings();
});
</script>
