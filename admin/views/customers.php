<?php
/**
 * Customers View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get customers with booking counts
$customers = $wpdb->get_results(
    "SELECT c.*, COUNT(b.id) as booking_count
    FROM {$wpdb->prefix}fluentbooking_customers c
    LEFT JOIN {$wpdb->prefix}fluentbooking_bookings b ON c.email = b.customer_email
    GROUP BY c.id
    ORDER BY c.created_at DESC",
    ARRAY_A
);
?>

<div class="wrap fluent-booking-customers">
    <h1><?php esc_html_e('Customers', 'fluent-bookings'); ?></h1>

    <div class="fb-customers-filters">
        <input type="text" id="fb-customer-search" placeholder="<?php esc_attr_e('Search customers...', 'fluent-bookings'); ?>">
        <button type="button" id="fb-apply-customer-filter" class="button"><?php esc_html_e('Search', 'fluent-bookings'); ?></button>
        <button type="button" id="fb-reset-customer-filter" class="button"><?php esc_html_e('Reset', 'fluent-bookings'); ?></button>
        <button type="button" id="fb-export-customers" class="button"><?php esc_html_e('Export CSV', 'fluent-bookings'); ?></button>
    </div>

    <?php if (!empty($customers)) : ?>
        <table class="wp-list-table widefat fixed striped fb-customers-table">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Name', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Email', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Phone', 'fluent-bookings'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Total Bookings', 'fluent-bookings'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Registered', 'fluent-bookings'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Actions', 'fluent-bookings'); ?></th>
                </tr>
            </thead>
            <tbody id="fb-customers-tbody">
                <?php foreach ($customers as $customer) : ?>
                    <tr>
                        <td><?php echo esc_html($customer['id']); ?></td>
                        <td><strong><?php echo esc_html($customer['name']); ?></strong></td>
                        <td><?php echo esc_html($customer['email']); ?></td>
                        <td><?php echo esc_html($customer['phone'] ? $customer['phone'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($customer['booking_count']); ?></td>
                        <td><?php echo esc_html(Fluent_Booking_Helper::format_date($customer['created_at'], 'M j, Y')); ?></td>
                        <td>
                            <button class="button button-small fb-view-customer-bookings" data-customer-id="<?php echo esc_attr($customer['id']); ?>" data-customer-email="<?php echo esc_attr($customer['email']); ?>">
                                <?php esc_html_e('View Bookings', 'fluent-bookings'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="fb-empty-state">
            <span class="dashicons dashicons-groups"></span>
            <h2><?php esc_html_e('No Customers Yet', 'fluent-bookings'); ?></h2>
            <p><?php esc_html_e('Customers will appear here once they make bookings.', 'fluent-bookings'); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Customer Bookings Modal -->
<div id="fb-customer-bookings-modal" class="fb-modal" style="display: none;">
    <div class="fb-modal-content fb-modal-large">
        <span class="fb-modal-close">&times;</span>
        <h2 id="fb-customer-modal-title"><?php esc_html_e('Customer Bookings', 'fluent-bookings'); ?></h2>
        <div id="fb-customer-bookings-content">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'fluent-bookings'); ?></th>
                        <th><?php esc_html_e('Date', 'fluent-bookings'); ?></th>
                        <th><?php esc_html_e('Time', 'fluent-bookings'); ?></th>
                        <th><?php esc_html_e('Duration', 'fluent-bookings'); ?></th>
                        <th><?php esc_html_e('Status', 'fluent-bookings'); ?></th>
                        <th><?php esc_html_e('Created', 'fluent-bookings'); ?></th>
                    </tr>
                </thead>
                <tbody id="fb-customer-bookings-tbody"></tbody>
            </table>
        </div>
        <div class="fb-modal-actions">
            <button type="button" class="button" id="fb-close-customer-modal">
                <?php esc_html_e('Close', 'fluent-bookings'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // View customer bookings
    jQuery(document).on('click', '.fb-view-customer-bookings', function() {
        var customerId = jQuery(this).data('customer-id');
        var customerEmail = jQuery(this).data('customer-email');

        jQuery('#fb-customer-modal-title').text('Bookings for ' + customerEmail);

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_get_customer_bookings',
                customer_email: customerEmail,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderCustomerBookings(response.data);
                    jQuery('#fb-customer-bookings-modal').show();
                } else {
                    alert('Error loading customer bookings');
                }
            }
        });
    });

    // Render customer bookings
    function renderCustomerBookings(bookings) {
        if (!bookings || bookings.length === 0) {
            jQuery('#fb-customer-bookings-tbody').html('<tr><td colspan="6">No bookings found</td></tr>');
            return;
        }

        var html = '';
        jQuery.each(bookings, function(index, booking) {
            html += '<tr>';
            html += '<td>' + booking.id + '</td>';
            html += '<td>' + formatDate(booking.booking_date) + '</td>';
            html += '<td>' + formatTime(booking.booking_time) + '</td>';
            html += '<td>' + booking.duration + ' min</td>';
            html += '<td>' + getStatusBadge(booking.status) + '</td>';
            html += '<td>' + formatDate(booking.created_at) + '</td>';
            html += '</tr>';
        });

        jQuery('#fb-customer-bookings-tbody').html(html);
    }

    // Helper functions
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatTime(timeString) {
        var time = new Date('2000-01-01 ' + timeString);
        return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    function getStatusBadge(status) {
        var badges = {
            'pending': '<span class="fb-status-badge fb-status-pending">Pending</span>',
            'confirmed': '<span class="fb-status-badge fb-status-confirmed">Confirmed</span>',
            'cancelled': '<span class="fb-status-badge fb-status-cancelled">Cancelled</span>',
            'completed': '<span class="fb-status-badge fb-status-completed">Completed</span>'
        };
        return badges[status] || status;
    }

    // Close modal
    jQuery('.fb-modal-close, #fb-close-customer-modal').on('click', function() {
        jQuery('#fb-customer-bookings-modal').hide();
    });

    jQuery('#fb-customer-bookings-modal').on('click', function(e) {
        if (e.target === this) {
            jQuery(this).hide();
        }
    });

    // Search functionality
    jQuery('#fb-apply-customer-filter').on('click', function() {
        var searchTerm = jQuery('#fb-customer-search').val().toLowerCase();

        jQuery('.fb-customers-table tbody tr').each(function() {
            var row = jQuery(this);
            var text = row.text().toLowerCase();

            if (text.indexOf(searchTerm) > -1 || searchTerm === '') {
                row.show();
            } else {
                row.hide();
            }
        });
    });

    jQuery('#fb-reset-customer-filter').on('click', function() {
        jQuery('#fb-customer-search').val('');
        jQuery('.fb-customers-table tbody tr').show();
    });

    // Export customers
    jQuery('#fb-export-customers').on('click', function() {
        var params = new URLSearchParams({
            action: 'fb_export_customers',
            nonce: fluentBookingAdmin.nonce
        });

        window.location.href = fluentBookingAdmin.ajaxurl + '?' + params.toString();
    });
});
</script>

<style>
.fb-customers-filters {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin: 20px 0;
    display: flex;
    gap: 10px;
}

.fb-customers-filters input {
    flex: 1;
    padding: 6px 12px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

.fb-modal-large .fb-modal-content {
    max-width: 900px;
}
</style>
