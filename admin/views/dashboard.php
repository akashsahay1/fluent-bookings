<?php
/**
 * Dashboard View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$forms_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fluentbooking_forms WHERE status = 'active'");
$bookings_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fluentbooking_bookings");
$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fluentbooking_bookings WHERE status = 'pending'");
$today_bookings = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}fluentbooking_bookings WHERE booking_date = %s",
    date('Y-m-d')
));

// Get recent bookings
$recent_bookings = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}fluentbooking_bookings ORDER BY created_at DESC LIMIT 10",
    ARRAY_A
);
?>

<div class="wrap fluent-booking-dashboard">
    <h1><?php esc_html_e('Fluent Bookings Dashboard', 'fluent-booking'); ?></h1>

    <div class="fb-stats-grid">
        <div class="fb-stat-card">
            <div class="fb-stat-icon">
                <span class="dashicons dashicons-forms"></span>
            </div>
            <div class="fb-stat-content">
                <h3><?php echo esc_html($forms_count); ?></h3>
                <p><?php esc_html_e('Active Forms', 'fluent-booking'); ?></p>
            </div>
        </div>

        <div class="fb-stat-card">
            <div class="fb-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="fb-stat-content">
                <h3><?php echo esc_html($bookings_count); ?></h3>
                <p><?php esc_html_e('Total Bookings', 'fluent-booking'); ?></p>
            </div>
        </div>

        <div class="fb-stat-card">
            <div class="fb-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="fb-stat-content">
                <h3><?php echo esc_html($pending_count); ?></h3>
                <p><?php esc_html_e('Pending Bookings', 'fluent-booking'); ?></p>
            </div>
        </div>

        <div class="fb-stat-card">
            <div class="fb-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="fb-stat-content">
                <h3><?php echo esc_html($today_bookings); ?></h3>
                <p><?php esc_html_e('Today\'s Bookings', 'fluent-booking'); ?></p>
            </div>
        </div>
    </div>

    <div class="fb-dashboard-content">
        <div class="fb-dashboard-section">
            <h2><?php esc_html_e('Recent Bookings', 'fluent-booking'); ?></h2>

            <?php if (!empty($recent_bookings)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Customer', 'fluent-booking'); ?></th>
                            <th><?php esc_html_e('Email', 'fluent-booking'); ?></th>
                            <th><?php esc_html_e('Date', 'fluent-booking'); ?></th>
                            <th><?php esc_html_e('Time', 'fluent-booking'); ?></th>
                            <th><?php esc_html_e('Status', 'fluent-booking'); ?></th>
                            <th><?php esc_html_e('Created', 'fluent-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking) : ?>
                            <tr>
                                <td><?php echo esc_html($booking['customer_name']); ?></td>
                                <td><?php echo esc_html($booking['customer_email']); ?></td>
                                <td><?php echo esc_html(Fluent_Booking_Helper::format_date($booking['booking_date'])); ?></td>
                                <td><?php echo esc_html(Fluent_Booking_Helper::format_time($booking['booking_time'])); ?></td>
                                <td><?php echo Fluent_Booking_Helper::get_status_badge($booking['status']); ?></td>
                                <td><?php echo esc_html(Fluent_Booking_Helper::format_date($booking['created_at'], 'M j, Y g:i A')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No bookings yet.', 'fluent-booking'); ?></p>
            <?php endif; ?>

            <p class="fb-view-all">
                <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-bookings')); ?>" class="button">
                    <?php esc_html_e('View All Bookings', 'fluent-booking'); ?>
                </a>
            </p>
        </div>

        <div class="fb-dashboard-sidebar">
            <div class="fb-widget">
                <h3><?php esc_html_e('Quick Actions', 'fluent-booking'); ?></h3>
                <ul class="fb-quick-actions">
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-add-form')); ?>">
                            <span class="dashicons dashicons-plus"></span>
                            <?php esc_html_e('Create New Form', 'fluent-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-calendar')); ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php esc_html_e('View Calendar', 'fluent-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-settings')); ?>">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e('Plugin Settings', 'fluent-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="fb-widget">
                <h3><?php esc_html_e('Documentation', 'fluent-booking'); ?></h3>
                <ul class="fb-docs-links">
                    <li>
                        <a href="#" target="_blank">
                            <?php esc_html_e('Getting Started Guide', 'fluent-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" target="_blank">
                            <?php esc_html_e('Using Shortcodes', 'fluent-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" target="_blank">
                            <?php esc_html_e('Google Calendar Setup', 'fluent-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
