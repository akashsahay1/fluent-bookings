<?php
/**
 * Form Handler Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Form_Handler {

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
        // AJAX handlers (both logged in and logged out users)
        add_action('wp_ajax_fb_submit_booking', array($this, 'submit_booking'));
        add_action('wp_ajax_nopriv_fb_submit_booking', array($this, 'submit_booking'));

        add_action('wp_ajax_fb_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_nopriv_fb_get_available_slots', array($this, 'get_available_slots'));

        add_action('wp_ajax_fb_check_date_availability', array($this, 'check_date_availability'));
        add_action('wp_ajax_nopriv_fb_check_date_availability', array($this, 'check_date_availability'));
    }

    /**
     * Submit booking
     */
    public function submit_booking() {
        // Verify nonce
        if (!isset($_POST['fb_nonce']) || !wp_verify_nonce($_POST['fb_nonce'], 'fluent_booking_public_nonce')) {
            Fluent_Booking_Helper::send_error(__('Security check failed', 'fluent-bookings'));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            Fluent_Booking_Helper::send_error(__('Invalid form', 'fluent-bookings'));
        }

        // Get form
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", $form_id), ARRAY_A);

        if (!$form) {
            Fluent_Booking_Helper::send_error(__('Form not found', 'fluent-bookings'));
        }

        $form_fields = json_decode($form['form_fields'], true);
        $form_settings = json_decode($form['form_settings'], true);

        // Collect form data
        $booking_data = array(
            'form_id' => $form_id
        );

        $form_submission_data = array();

        foreach ($form_fields as $field) {
            $field_id = $field['id'];
            $field_value = isset($_POST[$field_id]) ? $_POST[$field_id] : '';

            // Validate required fields
            if (isset($field['required']) && $field['required'] && empty($field_value)) {
                Fluent_Booking_Helper::send_error(
                    sprintf(__('Field "%s" is required', 'fluent-bookings'), $field['label'])
                );
            }

            // Sanitize based on field type
            switch ($field['type']) {
                case 'email':
                    $field_value = sanitize_email($field_value);
                    if (!is_email($field_value)) {
                        Fluent_Booking_Helper::send_error(__('Invalid email address', 'fluent-bookings'));
                    }
                    break;

                case 'textarea':
                    $field_value = sanitize_textarea_field($field_value);
                    break;

                case 'checkbox':
                    $field_value = array_map('sanitize_text_field', (array) $field_value);
                    break;

                default:
                    $field_value = sanitize_text_field($field_value);
                    break;
            }

            // Map to booking data
            if ($field_id === 'field_name') {
                $booking_data['customer_name'] = $field_value;
            } elseif ($field_id === 'field_email') {
                $booking_data['customer_email'] = $field_value;
            } elseif ($field_id === 'field_phone') {
                $booking_data['customer_phone'] = $field_value;
            } elseif ($field_id === 'field_date') {
                $booking_data['booking_date'] = $field_value;
            } elseif ($field_id === 'field_time') {
                $booking_data['booking_time'] = $field_value;
            } elseif ($field_id === 'field_notes') {
                $booking_data['customer_notes'] = $field_value;
            }

            $form_submission_data[$field_id] = $field_value;
        }

        // Add form data
        $booking_data['form_data'] = $form_submission_data;

        // Add duration from form settings
        if (isset($form_settings['duration'])) {
            $booking_data['duration'] = absint($form_settings['duration']);
        }

        // Create booking
        $booking_id = Fluent_Booking::create($booking_data);

        if (is_wp_error($booking_id)) {
            Fluent_Booking_Helper::send_error($booking_id->get_error_message());
        }

        // Get success message
        $success_message = isset($form_settings['confirmation_message'])
            ? $form_settings['confirmation_message']
            : __('Thank you! Your appointment has been booked successfully.', 'fluent-bookings');

        Fluent_Booking_Helper::send_success($success_message, array(
            'booking_id' => $booking_id,
            'redirect_url' => isset($form_settings['redirect_url']) ? $form_settings['redirect_url'] : ''
        ));
    }

    /**
     * Get available slots for a date
     */
    public function get_available_slots() {
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        if (!$form_id || !$date) {
            Fluent_Booking_Helper::send_error(__('Invalid parameters', 'fluent-bookings'));
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Fluent_Booking_Helper::send_error(__('Invalid date format', 'fluent-bookings'));
        }

        // Check if date is in the past
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            Fluent_Booking_Helper::send_error(__('Cannot book appointments in the past', 'fluent-bookings'));
        }

        // Get available slots
        $slots = Fluent_Booking_Availability::get_available_slots($form_id, $date);

        // Format slots for display
        $formatted_slots = array();
        foreach ($slots as $slot) {
            $formatted_slots[] = array(
                'value' => $slot,
                'label' => Fluent_Booking_Helper::format_time($slot)
            );
        }

        Fluent_Booking_Helper::send_success('', $formatted_slots);
    }

    /**
     * Check date availability (returns available dates for a date range)
     */
    public function check_date_availability() {
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d', strtotime('+30 days'));

        if (!$form_id) {
            Fluent_Booking_Helper::send_error(__('Invalid parameters', 'fluent-bookings'));
        }

        $available_dates = array();
        $unavailable_dates = array();

        // Check each date in the range
        $current = strtotime($start_date);
        $end = strtotime($end_date);

        while ($current <= $end) {
            $date = date('Y-m-d', $current);

            if (Fluent_Booking_Availability::is_date_available($form_id, $date)) {
                $available_dates[] = $date;
            } else {
                $unavailable_dates[] = $date;
            }

            $current = strtotime('+1 day', $current);
        }

        Fluent_Booking_Helper::send_success('', array(
            'available' => $available_dates,
            'unavailable' => $unavailable_dates
        ));
    }
}
