<?php
/**
 * Forms List View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all forms
$forms = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}fluentbooking_forms ORDER BY created_at DESC",
    ARRAY_A
);
?>

<div class="wrap fluent-booking-forms-list">
    <h1 class="wp-heading-inline"><?php esc_html_e('All Forms', 'fluent-bookings'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-add-form')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'fluent-bookings'); ?>
    </a>

    <hr class="wp-header-end">

    <?php if (!empty($forms)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Title', 'fluent-bookings'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'fluent-bookings'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Status', 'fluent-bookings'); ?></th>
                    <th style="width: 150px;"><?php esc_html_e('Created', 'fluent-bookings'); ?></th>
                    <th style="width: 200px;"><?php esc_html_e('Actions', 'fluent-bookings'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form) : ?>
                    <tr>
                        <td><?php echo esc_html($form['id']); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-edit-form&id=' . $form['id'])); ?>">
                                    <?php echo esc_html($form['title']); ?>
                                </a>
                            </strong>
                            <?php if (!empty($form['description'])) : ?>
                                <p class="description"><?php echo esc_html($form['description']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input
                                type="text"
                                value='[fluent_booking id="<?php echo esc_attr($form['id']); ?>"]'
                                readonly
                                onclick="this.select()"
                                style="width: 200px;"
                            >
                        </td>
                        <td>
                            <?php if ($form['status'] === 'active') : ?>
                                <span class="fb-status-badge fb-status-active"><?php esc_html_e('Active', 'fluent-bookings'); ?></span>
                            <?php else : ?>
                                <span class="fb-status-badge fb-status-inactive"><?php esc_html_e('Inactive', 'fluent-bookings'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(Fluent_Booking_Helper::format_date($form['created_at'], 'M j, Y')); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-edit-form&id=' . $form['id'])); ?>" class="button button-small">
                                <?php esc_html_e('Edit', 'fluent-bookings'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-availability&form_id=' . $form['id'])); ?>" class="button button-small">
                                <?php esc_html_e('Availability', 'fluent-bookings'); ?>
                            </a>
                            <button
                                class="button button-small fb-duplicate-form"
                                data-form-id="<?php echo esc_attr($form['id']); ?>"
                            >
                                <?php esc_html_e('Duplicate', 'fluent-bookings'); ?>
                            </button>
                            <button
                                class="button button-small fb-delete-form"
                                data-form-id="<?php echo esc_attr($form['id']); ?>"
                            >
                                <?php esc_html_e('Delete', 'fluent-bookings'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="fb-empty-state">
            <span class="dashicons dashicons-forms"></span>
            <h2><?php esc_html_e('No Forms Yet', 'fluent-bookings'); ?></h2>
            <p><?php esc_html_e('Create your first appointment booking form to get started.', 'fluent-bookings'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-add-form')); ?>" class="button button-primary button-large">
                <?php esc_html_e('Create Your First Form', 'fluent-bookings'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Duplicate form
    jQuery(document).on('click', '.fb-duplicate-form', function(e) {
        e.preventDefault();

        var formId = jQuery(this).data('form-id');

        if (!confirm('<?php esc_html_e('Are you sure you want to duplicate this form?', 'fluent-bookings'); ?>')) {
            return;
        }

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_duplicate_form',
                form_id: formId,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error duplicating form');
                }
            }
        });
    });

    // Delete form
    jQuery(document).on('click', '.fb-delete-form', function(e) {
        e.preventDefault();

        var formId = jQuery(this).data('form-id');

        if (!confirm(fluentBookingAdmin.strings.confirm_delete)) {
            return;
        }

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_delete_form',
                form_id: formId,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error deleting form');
                }
            }
        });
    });
});
</script>
