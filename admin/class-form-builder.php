<?php
/**
 * Form Builder Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Form_Builder {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_fb_save_form', array($this, 'save_form'));
        add_action('wp_ajax_fb_delete_form', array($this, 'delete_form'));
        add_action('wp_ajax_fb_duplicate_form', array($this, 'duplicate_form'));
        add_action('wp_ajax_fb_get_form', array($this, 'get_form'));
        add_action('wp_ajax_fb_update_form_status', array($this, 'update_form_status'));
    }

    /**
     * Save form
     */
    public function save_form() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $form_fields = isset($_POST['form_fields']) ? $_POST['form_fields'] : array();
        $form_settings = isset($_POST['form_settings']) ? $_POST['form_settings'] : array();
        $style_settings = isset($_POST['style_settings']) ? $_POST['style_settings'] : array();
        $notification_settings = isset($_POST['notification_settings']) ? $_POST['notification_settings'] : array();

        // Validate
        if (empty($title)) {
            Fluent_Booking_Helper::send_error(__('Form title is required', 'fluent-bookings'));
        }

        // Sanitize
        $form_fields = Fluent_Booking_Helper::sanitize_form_fields($form_fields);
        $style_settings = Fluent_Booking_Helper::sanitize_style_settings($style_settings);

        $data = array(
            'title' => $title,
            'description' => $description,
            'form_fields' => wp_json_encode($form_fields),
            'form_settings' => wp_json_encode($form_settings),
            'style_settings' => wp_json_encode($style_settings),
            'notification_settings' => wp_json_encode($notification_settings)
        );

        if ($form_id) {
            // Update existing form
            $wpdb->update(
                $table,
                $data,
                array('id' => $form_id),
                array('%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            Fluent_Booking_Helper::send_success(
                __('Form updated successfully', 'fluent-bookings'),
                array('form_id' => $form_id)
            );
        } else {
            // Create new form
            $data['created_by'] = get_current_user_id();
            $data['status'] = 'active';

            $wpdb->insert(
                $table,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );

            $form_id = $wpdb->insert_id;

            // Create default availability for new form
            $this->create_default_availability($form_id);

            Fluent_Booking_Helper::send_success(
                __('Form created successfully', 'fluent-bookings'),
                array('form_id' => $form_id)
            );
        }
    }

    /**
     * Create default availability for new form
     */
    private function create_default_availability($form_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_availability';

        // Monday to Friday, 9 AM to 5 PM
        for ($day = 1; $day <= 5; $day++) {
            $wpdb->insert(
                $table,
                array(
                    'form_id' => $form_id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'is_available' => 1,
                    'slot_duration' => 30,
                    'buffer_time' => 0
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%d')
            );
        }
    }

    /**
     * Delete form
     */
    public function delete_form() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            Fluent_Booking_Helper::send_error(__('Invalid form ID', 'fluent-bookings'));
        }

        // Delete form
        $wpdb->delete(
            $wpdb->prefix . 'fluentbooking_forms',
            array('id' => $form_id),
            array('%d')
        );

        // Delete related availability
        $wpdb->delete(
            $wpdb->prefix . 'fluentbooking_availability',
            array('form_id' => $form_id),
            array('%d')
        );

        // Delete related notifications
        $wpdb->delete(
            $wpdb->prefix . 'fluentbooking_notifications',
            array('form_id' => $form_id),
            array('%d')
        );

        Fluent_Booking_Helper::send_success(__('Form deleted successfully', 'fluent-bookings'));
    }

    /**
     * Duplicate form
     */
    public function duplicate_form() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            Fluent_Booking_Helper::send_error(__('Invalid form ID', 'fluent-bookings'));
        }

        // Get original form
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id), ARRAY_A);

        if (!$form) {
            Fluent_Booking_Helper::send_error(__('Form not found', 'fluent-bookings'));
        }

        // Remove ID and update title
        unset($form['id']);
        $form['title'] = $form['title'] . ' (Copy)';
        $form['created_by'] = get_current_user_id();
        $form['created_at'] = current_time('mysql');
        $form['updated_at'] = current_time('mysql');

        // Insert duplicate
        $wpdb->insert($table, $form);
        $new_form_id = $wpdb->insert_id;

        // Duplicate availability
        $availability_table = $wpdb->prefix . 'fluentbooking_availability';
        $availability_rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table WHERE form_id = %d",
            $form_id
        ), ARRAY_A);

        foreach ($availability_rules as $rule) {
            unset($rule['id']);
            $rule['form_id'] = $new_form_id;
            $rule['created_at'] = current_time('mysql');
            $wpdb->insert($availability_table, $rule);
        }

        // Duplicate notifications
        $notifications_table = $wpdb->prefix . 'fluentbooking_notifications';
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $notifications_table WHERE form_id = %d",
            $form_id
        ), ARRAY_A);

        foreach ($notifications as $notification) {
            unset($notification['id']);
            $notification['form_id'] = $new_form_id;
            $notification['created_at'] = current_time('mysql');
            $notification['updated_at'] = current_time('mysql');
            $wpdb->insert($notifications_table, $notification);
        }

        Fluent_Booking_Helper::send_success(
            __('Form duplicated successfully', 'fluent-bookings'),
            array('form_id' => $new_form_id)
        );
    }

    /**
     * Get form
     */
    public function get_form() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            Fluent_Booking_Helper::send_error(__('Invalid form ID', 'fluent-bookings'));
        }

        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id), ARRAY_A);

        if (!$form) {
            Fluent_Booking_Helper::send_error(__('Form not found', 'fluent-bookings'));
        }

        // Decode JSON fields
        $form['form_fields'] = json_decode($form['form_fields'], true);
        $form['form_settings'] = json_decode($form['form_settings'], true);
        $form['style_settings'] = json_decode($form['style_settings'], true);
        $form['notification_settings'] = json_decode($form['notification_settings'], true);

        Fluent_Booking_Helper::send_success('', $form);
    }

    /**
     * Update form status
     */
    public function update_form_status() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$form_id || !in_array($status, array('active', 'inactive'))) {
            Fluent_Booking_Helper::send_error(__('Invalid parameters', 'fluent-bookings'));
        }

        $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $form_id),
            array('%s'),
            array('%d')
        );

        Fluent_Booking_Helper::send_success(__('Status updated successfully', 'fluent-bookings'));
    }

    /**
     * Get available field types
     */
    public static function get_field_types() {
        return array(
            'text' => array(
                'label' => __('Text Input', 'fluent-bookings'),
                'icon' => 'dashicons-edit'
            ),
            'email' => array(
                'label' => __('Email', 'fluent-bookings'),
                'icon' => 'dashicons-email'
            ),
            'tel' => array(
                'label' => __('Phone', 'fluent-bookings'),
                'icon' => 'dashicons-phone'
            ),
            'textarea' => array(
                'label' => __('Textarea', 'fluent-bookings'),
                'icon' => 'dashicons-text'
            ),
            'select' => array(
                'label' => __('Dropdown', 'fluent-bookings'),
                'icon' => 'dashicons-arrow-down-alt2'
            ),
            'radio' => array(
                'label' => __('Radio Buttons', 'fluent-bookings'),
                'icon' => 'dashicons-marker'
            ),
            'checkbox' => array(
                'label' => __('Checkboxes', 'fluent-bookings'),
                'icon' => 'dashicons-yes'
            ),
            'date' => array(
                'label' => __('Date Picker', 'fluent-bookings'),
                'icon' => 'dashicons-calendar-alt'
            ),
            'time' => array(
                'label' => __('Time Picker', 'fluent-bookings'),
                'icon' => 'dashicons-clock'
            ),
            'number' => array(
                'label' => __('Number', 'fluent-bookings'),
                'icon' => 'dashicons-calculator'
            )
        );
    }
}
