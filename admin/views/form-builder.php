<?php
/**
 * Form Builder View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$is_edit = $form_id > 0;

$form_data = null;

if ($is_edit) {
    global $wpdb;
    $table = $wpdb->prefix . 'fluentbooking_forms';
    $form_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id), ARRAY_A);

    if (!$form_data) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Form not found', 'fluent-booking') . '</p></div>';
        return;
    }

    // Decode JSON fields
    $form_data['form_fields'] = json_decode($form_data['form_fields'], true);
    $form_data['form_settings'] = json_decode($form_data['form_settings'], true);
    $form_data['style_settings'] = json_decode($form_data['style_settings'], true);
    $form_data['notification_settings'] = json_decode($form_data['notification_settings'], true);
}

$field_types = Fluent_Booking_Form_Builder::get_field_types();
?>

<div class="wrap fluent-booking-form-builder" id="fb-form-builder">
    <h1><?php echo $is_edit ? esc_html__('Edit Form', 'fluent-booking') : esc_html__('Create New Form', 'fluent-booking'); ?></h1>

    <div class="fb-builder-container">
        <!-- Sidebar with field types -->
        <div class="fb-builder-sidebar">
            <h3><?php esc_html_e('Form Fields', 'fluent-booking'); ?></h3>

            <div class="fb-field-types">
                <?php foreach ($field_types as $type => $field) : ?>
                    <div class="fb-field-type" data-field-type="<?php echo esc_attr($type); ?>">
                        <span class="dashicons <?php echo esc_attr($field['icon']); ?>"></span>
                        <span class="fb-field-label"><?php echo esc_html($field['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main builder area -->
        <div class="fb-builder-main">
            <!-- Tabs -->
            <div class="fb-builder-tabs">
                <button class="fb-tab-button active" data-tab="fields"><?php esc_html_e('Fields', 'fluent-booking'); ?></button>
                <button class="fb-tab-button" data-tab="settings"><?php esc_html_e('Settings', 'fluent-booking'); ?></button>
                <button class="fb-tab-button" data-tab="styles"><?php esc_html_e('Styles', 'fluent-booking'); ?></button>
                <button class="fb-tab-button" data-tab="notifications"><?php esc_html_e('Notifications', 'fluent-booking'); ?></button>
            </div>

            <!-- Tab: Fields -->
            <div class="fb-tab-content active" data-tab-content="fields">
                <div class="fb-form-header">
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Form Title', 'fluent-booking'); ?></label>
                        <input
                            type="text"
                            id="fb-form-title"
                            placeholder="<?php esc_attr_e('Enter form title', 'fluent-booking'); ?>"
                            value="<?php echo $is_edit ? esc_attr($form_data['title']) : ''; ?>"
                        >
                    </div>

                    <div class="fb-form-group">
                        <label><?php esc_html_e('Form Description (Optional)', 'fluent-booking'); ?></label>
                        <textarea
                            id="fb-form-description"
                            rows="3"
                            placeholder="<?php esc_attr_e('Enter form description', 'fluent-booking'); ?>"
                        ><?php echo $is_edit ? esc_textarea($form_data['description']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="fb-form-fields-area">
                    <h3><?php esc_html_e('Form Fields', 'fluent-booking'); ?></h3>
                    <p class="description"><?php esc_html_e('Drag fields from the left panel or reorder existing fields.', 'fluent-booking'); ?></p>

                    <div id="fb-fields-container" class="fb-fields-container">
                        <!-- Fields will be added here dynamically -->
                    </div>
                </div>
            </div>

            <!-- Tab: Settings -->
            <div class="fb-tab-content" data-tab-content="settings">
                <h3><?php esc_html_e('Form Settings', 'fluent-booking'); ?></h3>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Appointment Duration (minutes)', 'fluent-booking'); ?></label>
                    <input
                        type="number"
                        id="fb-setting-duration"
                        value="30"
                        min="5"
                        max="480"
                    >
                </div>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Buffer Time Between Appointments (minutes)', 'fluent-booking'); ?></label>
                    <input
                        type="number"
                        id="fb-setting-buffer"
                        value="0"
                        min="0"
                        max="120"
                    >
                </div>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Minimum Booking Notice (hours)', 'fluent-booking'); ?></label>
                    <input
                        type="number"
                        id="fb-setting-min-notice"
                        value="24"
                        min="0"
                    >
                </div>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Maximum Days in Advance', 'fluent-booking'); ?></label>
                    <input
                        type="number"
                        id="fb-setting-max-advance"
                        value="30"
                        min="1"
                    >
                </div>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Confirmation Message', 'fluent-booking'); ?></label>
                    <textarea
                        id="fb-setting-confirmation"
                        rows="4"
                    ><?php esc_html_e('Thank you! Your appointment has been booked successfully.', 'fluent-booking'); ?></textarea>
                </div>

                <div class="fb-form-group">
                    <label><?php esc_html_e('Success Action', 'fluent-booking'); ?></label>
                    <select id="fb-setting-success-action">
                        <option value="message"><?php esc_html_e('Show Message', 'fluent-booking'); ?></option>
                        <option value="redirect"><?php esc_html_e('Redirect to URL', 'fluent-booking'); ?></option>
                    </select>
                </div>

                <div class="fb-form-group fb-redirect-url-group" style="display: none;">
                    <label><?php esc_html_e('Redirect URL', 'fluent-booking'); ?></label>
                    <input
                        type="url"
                        id="fb-setting-redirect-url"
                        placeholder="https://"
                    >
                </div>
            </div>

            <!-- Tab: Styles -->
            <div class="fb-tab-content" data-tab-content="styles">
                <h3><?php esc_html_e('Form Styles', 'fluent-booking'); ?></h3>

                <div class="fb-style-section">
                    <h4><?php esc_html_e('Form Container', 'fluent-booking'); ?></h4>
                    <div class="fb-style-row">
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Background Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-form-bg" value="#ffffff">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-form-border-color" value="#e0e0e0">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Width (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-form-border-width" value="1" min="0" max="10">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Radius (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-form-border-radius" value="5" min="0" max="50">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Padding (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-form-padding" value="20" min="0" max="100">
                        </div>
                    </div>
                </div>

                <div class="fb-style-section">
                    <h4><?php esc_html_e('Labels', 'fluent-booking'); ?></h4>
                    <div class="fb-style-row">
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-label-color" value="#333333">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Size (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-label-font-size" value="14" min="10" max="30">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Weight', 'fluent-booking'); ?></label>
                            <select id="fb-style-label-font-weight">
                                <option value="400">Normal</option>
                                <option value="600" selected>Semi-Bold</option>
                                <option value="700">Bold</option>
                            </select>
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Family', 'fluent-booking'); ?></label>
                            <input type="text" id="fb-style-label-font-family" value="inherit">
                        </div>
                    </div>
                </div>

                <div class="fb-style-section">
                    <h4><?php esc_html_e('Input Fields', 'fluent-booking'); ?></h4>
                    <div class="fb-style-row">
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Background Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-field-bg" value="#ffffff">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-field-border-color" value="#cccccc">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Width (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-field-border-width" value="1" min="0" max="5">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Radius (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-field-border-radius" value="3" min="0" max="20">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Padding (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-field-padding" value="10" min="0" max="30">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Text Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-field-color" value="#333333">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Size (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-field-font-size" value="14" min="10" max="24">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Family', 'fluent-booking'); ?></label>
                            <input type="text" id="fb-style-field-font-family" value="inherit">
                        </div>
                    </div>
                </div>

                <div class="fb-style-section">
                    <h4><?php esc_html_e('Submit Button', 'fluent-booking'); ?></h4>
                    <div class="fb-style-row">
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Background Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-button-bg" value="#0073aa">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Text Color', 'fluent-booking'); ?></label>
                            <input type="text" class="fb-color-picker" id="fb-style-button-color" value="#ffffff">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Border Radius (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-button-border-radius" value="3" min="0" max="50">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Padding (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-button-padding" value="12" min="5" max="30">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Size (px)', 'fluent-booking'); ?></label>
                            <input type="number" id="fb-style-button-font-size" value="16" min="12" max="24">
                        </div>
                        <div class="fb-form-group">
                            <label><?php esc_html_e('Font Weight', 'fluent-booking'); ?></label>
                            <select id="fb-style-button-font-weight">
                                <option value="400">Normal</option>
                                <option value="600" selected>Semi-Bold</option>
                                <option value="700">Bold</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Notifications -->
            <div class="fb-tab-content" data-tab-content="notifications">
                <h3><?php esc_html_e('Email Notifications', 'fluent-booking'); ?></h3>

                <div class="fb-notification-section">
                    <h4><?php esc_html_e('Customer Notification', 'fluent-booking'); ?></h4>
                    <div class="fb-form-group">
                        <label>
                            <input type="checkbox" id="fb-notif-customer-enabled" checked>
                            <?php esc_html_e('Send confirmation email to customer', 'fluent-booking'); ?>
                        </label>
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Email Subject', 'fluent-booking'); ?></label>
                        <input
                            type="text"
                            id="fb-notif-customer-subject"
                            value="Appointment Confirmation - {{booking_date}}"
                        >
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Email Message', 'fluent-booking'); ?></label>
                        <textarea
                            id="fb-notif-customer-message"
                            rows="6"
                        >Hi {{customer_name}},

Your appointment has been confirmed for {{booking_date}} at {{booking_time}}.

Thank you!</textarea>
                    </div>
                </div>

                <div class="fb-notification-section">
                    <h4><?php esc_html_e('Admin Notification', 'fluent-booking'); ?></h4>
                    <div class="fb-form-group">
                        <label>
                            <input type="checkbox" id="fb-notif-admin-enabled" checked>
                            <?php esc_html_e('Send notification to admin', 'fluent-booking'); ?>
                        </label>
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Email Subject', 'fluent-booking'); ?></label>
                        <input
                            type="text"
                            id="fb-notif-admin-subject"
                            value="New Appointment Booking"
                        >
                    </div>
                    <div class="fb-form-group">
                        <label><?php esc_html_e('Email Message', 'fluent-booking'); ?></label>
                        <textarea
                            id="fb-notif-admin-message"
                            rows="6"
                        >New appointment booking received from {{customer_name}} ({{customer_email}}) for {{booking_date}} at {{booking_time}}.</textarea>
                    </div>
                </div>

                <div class="fb-notification-section">
                    <p class="description">
                        <?php esc_html_e('Available merge tags:', 'fluent-booking'); ?>
                        <code>{{customer_name}}</code>,
                        <code>{{customer_email}}</code>,
                        <code>{{customer_phone}}</code>,
                        <code>{{booking_date}}</code>,
                        <code>{{booking_time}}</code>,
                        <code>{{booking_id}}</code>,
                        <code>{{site_name}}</code>,
                        <code>{{site_url}}</code>
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="fb-builder-actions">
                <button type="button" id="fb-save-form" class="button button-primary button-large">
                    <?php echo $is_edit ? esc_html__('Update Form', 'fluent-booking') : esc_html__('Create Form', 'fluent-booking'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=fluent-booking-forms')); ?>" class="button button-large">
                    <?php esc_html_e('Cancel', 'fluent-booking'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="fb-form-id" value="<?php echo $is_edit ? esc_attr($form_id) : '0'; ?>">
<input type="hidden" id="fb-form-data" value='<?php echo $is_edit ? esc_attr(wp_json_encode($form_data)) : ''; ?>'>
