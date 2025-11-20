<?php
/**
 * Availability Management View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;

if (!$form_id) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Please select a form', 'fluent-booking') . '</p></div>';
    return;
}

global $wpdb;

// Get form
$form = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}fluentbooking_forms WHERE id = %d",
    $form_id
), ARRAY_A);

if (!$form) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Form not found', 'fluent-booking') . '</p></div>';
    return;
}

// Get availability rules
$availability_rules = Fluent_Booking_Availability::get_rules($form_id);

// Get blocked dates
$blocked_dates = Fluent_Booking_Availability::get_blocked_dates($form_id);

$days_of_week = Fluent_Booking_Helper::get_days_of_week();
?>

<div class="wrap fluent-booking-availability">
    <?php /* translators: %s: Form title */ ?>
    <h1><?php echo esc_html(sprintf(__('Availability - %s', 'fluent-booking'), $form['title'])); ?></h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-forms')); ?>" class="button">
        <?php esc_html_e('← Back to Forms', 'fluent-booking'); ?>
    </a>

    <!-- Tabs -->
    <div class="fb-availability-tabs">
        <button class="fb-av-tab-button active" data-tab="weekly"><?php esc_html_e('Weekly Schedule', 'fluent-booking'); ?></button>
        <button class="fb-av-tab-button" data-tab="blocked"><?php esc_html_e('Blocked Dates', 'fluent-booking'); ?></button>
    </div>

    <!-- Weekly Schedule Tab -->
    <div class="fb-av-tab-content active" data-tab-content="weekly">
        <div class="fb-av-section">
            <h2><?php esc_html_e('Weekly Availability Schedule', 'fluent-booking'); ?></h2>
            <p class="description"><?php esc_html_e('Configure your available hours for each day of the week.', 'fluent-booking'); ?></p>

            <table class="wp-list-table widefat fixed striped fb-availability-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('Enabled', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Day', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Start Time', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('End Time', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Slot Duration (min)', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Buffer Time (min)', 'fluent-booking'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Actions', 'fluent-booking'); ?></th>
                    </tr>
                </thead>
                <tbody id="fb-availability-tbody">
                    <?php
                    // Group rules by day
                    $rules_by_day = array();
                    foreach ($availability_rules as $rule) {
                        if (!isset($rules_by_day[$rule['day_of_week']])) {
                            $rules_by_day[$rule['day_of_week']] = array();
                        }
                        $rules_by_day[$rule['day_of_week']][] = $rule;
                    }

                    // Display all days
                    for ($day = 0; $day <= 6; $day++) :
                        $day_rules = isset($rules_by_day[$day]) ? $rules_by_day[$day] : array();

                        if (empty($day_rules)) {
                            // No rules for this day, show disabled row
                            ?>
                            <tr data-day="<?php echo $day; ?>">
                                <td>
                                    <input type="checkbox" class="fb-day-enabled" data-day="<?php echo $day; ?>">
                                </td>
                                <td><strong><?php echo esc_html($days_of_week[$day]); ?></strong></td>
                                <td>
                                    <input type="time" class="fb-start-time" value="09:00" disabled>
                                </td>
                                <td>
                                    <input type="time" class="fb-end-time" value="17:00" disabled>
                                </td>
                                <td>
                                    <input type="number" class="fb-slot-duration" value="30" min="5" max="480" disabled>
                                </td>
                                <td>
                                    <input type="number" class="fb-buffer-time" value="0" min="0" max="120" disabled>
                                </td>
                                <td></td>
                            </tr>
                            <?php
                        } else {
                            // Show existing rules
                            foreach ($day_rules as $index => $rule) :
                                ?>
                                <tr data-day="<?php echo $day; ?>" data-rule-id="<?php echo $rule['id']; ?>">
                                    <td>
                                        <?php if ($index === 0) : ?>
                                            <input type="checkbox" class="fb-day-enabled" data-day="<?php echo $day; ?>" <?php checked($rule['is_available'], 1); ?>>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($index === 0) : ?>
                                            <strong><?php echo esc_html($days_of_week[$day]); ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="time" class="fb-start-time" value="<?php echo esc_attr(substr($rule['start_time'], 0, 5)); ?>" <?php disabled($rule['is_available'], 0); ?>>
                                    </td>
                                    <td>
                                        <input type="time" class="fb-end-time" value="<?php echo esc_attr(substr($rule['end_time'], 0, 5)); ?>" <?php disabled($rule['is_available'], 0); ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="fb-slot-duration" value="<?php echo esc_attr($rule['slot_duration']); ?>" min="5" max="480" <?php disabled($rule['is_available'], 0); ?>>
                                    </td>
                                    <td>
                                        <input type="number" class="fb-buffer-time" value="<?php echo esc_attr($rule['buffer_time']); ?>" min="0" max="120" <?php disabled($rule['is_available'], 0); ?>>
                                    </td>
                                    <td>
                                        <?php if (count($day_rules) > 1) : ?>
                                            <button type="button" class="button button-small fb-delete-time-slot" data-rule-id="<?php echo $rule['id']; ?>">
                                                <?php esc_html_e('Delete', 'fluent-booking'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            endforeach;

                            // Add time slot button
                            ?>
                            <tr data-day="<?php echo $day; ?>" class="fb-add-slot-row">
                                <td colspan="7">
                                    <button type="button" class="button button-small fb-add-time-slot" data-day="<?php echo $day; ?>">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php esc_html_e('Add Another Time Slot', 'fluent-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    endfor;
                    ?>
                </tbody>
            </table>

            <div class="fb-av-actions">
                <button type="button" id="fb-save-availability" class="button button-primary button-large">
                    <?php esc_html_e('Save Availability', 'fluent-booking'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Blocked Dates Tab -->
    <div class="fb-av-tab-content" data-tab-content="blocked">
        <div class="fb-av-section">
            <h2><?php esc_html_e('Blocked Dates', 'fluent-booking'); ?></h2>
            <p class="description"><?php esc_html_e('Block specific dates or date ranges when you are not available.', 'fluent-booking'); ?></p>

            <div class="fb-block-date-form">
                <h3><?php esc_html_e('Add Blocked Date', 'fluent-booking'); ?></h3>
                <div class="fb-form-row">
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Date', 'fluent-booking'); ?></label>
                        <input type="date" id="fb-block-date" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Block Type', 'fluent-booking'); ?></label>
                        <select id="fb-block-type">
                            <option value="full_day"><?php esc_html_e('Full Day', 'fluent-booking'); ?></option>
                            <option value="time_range"><?php esc_html_e('Specific Time Range', 'fluent-booking'); ?></option>
                        </select>
                    </div>
                    <div class="fb-form-group fb-block-time-fields" style="display: none;">
                        <label><?php esc_html_e('From Time', 'fluent-booking'); ?></label>
                        <input type="time" id="fb-block-from">
                    </div>
                    <div class="fb-form-group fb-block-time-fields" style="display: none;">
                        <label><?php esc_html_e('To Time', 'fluent-booking'); ?></label>
                        <input type="time" id="fb-block-to">
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Reason (Optional)', 'fluent-booking'); ?></label>
                        <input type="text" id="fb-block-reason" placeholder="<?php esc_attr_e('e.g., Holiday, Vacation', 'fluent-booking'); ?>">
                    </div>
                    <div class="fb-form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="fb-add-blocked-date" class="button button-primary">
                            <?php esc_html_e('Block Date', 'fluent-booking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Time Range', 'fluent-booking'); ?></th>
                        <th><?php esc_html_e('Reason', 'fluent-booking'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Actions', 'fluent-booking'); ?></th>
                    </tr>
                </thead>
                <tbody id="fb-blocked-dates-tbody">
                    <?php if (!empty($blocked_dates)) : ?>
                        <?php foreach ($blocked_dates as $blocked) : ?>
                            <tr>
                                <td><?php echo esc_html(Fluent_Booking_Helper::format_date($blocked['blocked_date'])); ?></td>
                                <td>
                                    <?php
                                    if (empty($blocked['blocked_from']) && empty($blocked['blocked_to'])) {
                                        esc_html_e('Full Day', 'fluent-booking');
                                    } else {
                                        echo esc_html(Fluent_Booking_Helper::format_time($blocked['blocked_from']) . ' - ' . Fluent_Booking_Helper::format_time($blocked['blocked_to']));
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($blocked['reason'] ? $blocked['reason'] : '-'); ?></td>
                                <td>
                                    <button type="button" class="button button-small fb-delete-blocked-date" data-block-id="<?php echo esc_attr($blocked['id']); ?>">
                                        <?php esc_html_e('Delete', 'fluent-booking'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No blocked dates yet.', 'fluent-booking'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<input type="hidden" id="fb-form-id" value="<?php echo esc_attr($form_id); ?>">

<script type="text/javascript">
jQuery(document).ready(function($) {
    var formId = jQuery('#fb-form-id').val();

    // Tab switching
    jQuery('.fb-av-tab-button').on('click', function() {
        var tab = jQuery(this).data('tab');

        jQuery('.fb-av-tab-button').removeClass('active');
        jQuery(this).addClass('active');

        jQuery('.fb-av-tab-content').removeClass('active');
        jQuery('[data-tab-content="' + tab + '"]').addClass('active');
    });

    // Enable/disable day
    jQuery(document).on('change', '.fb-day-enabled', function() {
        var row = jQuery(this).closest('tr');
        var isChecked = jQuery(this).is(':checked');

        row.find('input:not(.fb-day-enabled)').prop('disabled', !isChecked);
    });

    // Block type change
    jQuery('#fb-block-type').on('change', function() {
        if (jQuery(this).val() === 'time_range') {
            jQuery('.fb-block-time-fields').show();
        } else {
            jQuery('.fb-block-time-fields').hide();
        }
    });

    // Save availability
    jQuery('#fb-save-availability').on('click', function() {
        var rules = [];

        jQuery('#fb-availability-tbody tr[data-day]').not('.fb-add-slot-row').each(function() {
            var row = jQuery(this);
            var day = row.data('day');
            var isEnabled = row.find('.fb-day-enabled').is(':checked');

            if (isEnabled) {
                rules.push({
                    day_of_week: day,
                    start_time: row.find('.fb-start-time').val() + ':00',
                    end_time: row.find('.fb-end-time').val() + ':00',
                    is_available: 1,
                    slot_duration: parseInt(row.find('.fb-slot-duration').val()),
                    buffer_time: parseInt(row.find('.fb-buffer-time').val())
                });
            }
        });

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_save_availability',
                form_id: formId,
                rules: rules,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(fluentBookingAdmin.strings.save_success);
                    location.reload();
                } else {
                    alert(response.data.message || fluentBookingAdmin.strings.save_error);
                }
            }
        });
    });

    // Add blocked date
    jQuery('#fb-add-blocked-date').on('click', function() {
        var date = jQuery('#fb-block-date').val();
        var blockType = jQuery('#fb-block-type').val();
        var blockFrom = blockType === 'time_range' ? jQuery('#fb-block-from').val() : null;
        var blockTo = blockType === 'time_range' ? jQuery('#fb-block-to').val() : null;
        var reason = jQuery('#fb-block-reason').val();

        if (!date) {
            alert('Please select a date');
            return;
        }

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_block_date',
                form_id: formId,
                date: date,
                block_from: blockFrom,
                block_to: blockTo,
                reason: reason,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Date blocked successfully');
                    location.reload();
                } else {
                    alert(response.data.message || 'Error blocking date');
                }
            }
        });
    });

    // Delete blocked date
    jQuery(document).on('click', '.fb-delete-blocked-date', function() {
        if (!confirm('Are you sure you want to unblock this date?')) {
            return;
        }

        var blockId = jQuery(this).data('block-id');

        jQuery.ajax({
            url: fluentBookingAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'fb_unblock_date',
                block_id: blockId,
                nonce: fluentBookingAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Date unblocked successfully');
                    location.reload();
                } else {
                    alert(response.data.message || 'Error unblocking date');
                }
            }
        });
    });
});
</script>

<style>
.fb-availability-tabs {
    display: flex;
    gap: 5px;
    border-bottom: 1px solid #ddd;
    margin: 20px 0;
}

.fb-av-tab-button {
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.2s;
}

.fb-av-tab-button:hover {
    color: #0073aa;
}

.fb-av-tab-button.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
}

.fb-av-tab-content {
    display: none;
}

.fb-av-tab-content.active {
    display: block;
}

.fb-av-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-top: 20px;
}

.fb-availability-table input[type="time"],
.fb-availability-table input[type="number"] {
    width: 100%;
    padding: 5px;
}

.fb-av-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.fb-block-date-form {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 20px;
}

.fb-form-row {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.fb-form-row .fb-form-group {
    flex: 1;
}

.fb-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.fb-form-group input,
.fb-form-group select {
    width: 100%;
    padding: 6px 10px;
}

.fb-add-slot-row {
    background: #f9f9f9;
}

.fb-add-time-slot {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
</style>
