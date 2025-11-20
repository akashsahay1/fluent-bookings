<?php
/**
 * Helper Functions Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Helper {

    /**
     * Sanitize form fields
     */
    public static function sanitize_form_fields($fields) {
        if (!is_array($fields)) {
            return array();
        }

        $sanitized = array();

        foreach ($fields as $field) {
            $sanitized[] = array(
                'id' => sanitize_key($field['id']),
                'type' => sanitize_text_field($field['type']),
                'label' => sanitize_text_field($field['label']),
                'placeholder' => isset($field['placeholder']) ? sanitize_text_field($field['placeholder']) : '',
                'required' => isset($field['required']) ? (bool) $field['required'] : false,
                'width' => isset($field['width']) ? absint($field['width']) : 100,
                'order' => isset($field['order']) ? absint($field['order']) : 0,
                'show_label' => isset($field['show_label']) ? (bool) $field['show_label'] : true,
                'options' => isset($field['options']) ? array_map('sanitize_text_field', (array) $field['options']) : array()
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize style settings
     */
    public static function sanitize_style_settings($styles) {
        if (!is_array($styles)) {
            return array();
        }

        $sanitized = array();
        $allowed_keys = array(
            'form_background', 'form_border_color', 'form_border_width', 'form_border_radius', 'form_padding',
            'label_color', 'label_font_size', 'label_font_weight', 'label_font_family',
            'field_background', 'field_border_color', 'field_border_width', 'field_border_radius',
            'field_padding', 'field_color', 'field_font_size', 'field_font_family',
            'button_background', 'button_color', 'button_border_radius', 'button_padding',
            'button_font_size', 'button_font_weight'
        );

        foreach ($allowed_keys as $key) {
            if (isset($styles[$key])) {
                if (strpos($key, 'color') !== false || strpos($key, 'background') !== false) {
                    $sanitized[$key] = sanitize_hex_color($styles[$key]);
                } elseif (strpos($key, 'font_family') !== false) {
                    $sanitized[$key] = sanitize_text_field($styles[$key]);
                } else {
                    $sanitized[$key] = sanitize_text_field($styles[$key]);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Format date for display
     */
    public static function format_date($date, $format = 'F j, Y') {
        if (empty($date)) {
            return '';
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date_i18n($format, $timestamp);
    }

    /**
     * Format time for display
     */
    public static function format_time($time, $format = 'g:i A') {
        if (empty($time)) {
            return '';
        }

        $timestamp = is_numeric($time) ? $time : strtotime($time);
        return date_i18n($format, $timestamp);
    }

    /**
     * Get booking statuses
     */
    public static function get_booking_statuses() {
        return array(
            'pending' => __('Pending', 'fluent-booking'),
            'confirmed' => __('Confirmed', 'fluent-booking'),
            'cancelled' => __('Cancelled', 'fluent-booking'),
            'completed' => __('Completed', 'fluent-booking')
        );
    }

    /**
     * Get booking status badge HTML
     */
    public static function get_status_badge($status) {
        $statuses = self::get_booking_statuses();
        $label = isset($statuses[$status]) ? $statuses[$status] : $status;

        $class = 'fb-status-badge fb-status-' . esc_attr($status);

        return '<span class="' . $class . '">' . esc_html($label) . '</span>';
    }

    /**
     * Parse email merge tags
     */
    public static function parse_merge_tags($content, $booking_data) {
        $tags = array(
            '{{customer_name}}' => isset($booking_data['customer_name']) ? $booking_data['customer_name'] : '',
            '{{customer_email}}' => isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '',
            '{{customer_phone}}' => isset($booking_data['customer_phone']) ? $booking_data['customer_phone'] : '',
            '{{booking_date}}' => isset($booking_data['booking_date']) ? self::format_date($booking_data['booking_date']) : '',
            '{{booking_time}}' => isset($booking_data['booking_time']) ? self::format_time($booking_data['booking_time']) : '',
            '{{booking_id}}' => isset($booking_data['id']) ? $booking_data['id'] : '',
            '{{site_name}}' => get_bloginfo('name'),
            '{{site_url}}' => get_site_url()
        );

        return str_replace(array_keys($tags), array_values($tags), $content);
    }

    /**
     * Send JSON response
     */
    public static function send_json($data = array(), $status_code = 200) {
        wp_send_json($data, $status_code);
    }

    /**
     * Send success JSON response
     */
    public static function send_success($message = '', $data = array()) {
        wp_send_json_success(array(
            'message' => $message,
            'data' => $data
        ));
    }

    /**
     * Send error JSON response
     */
    public static function send_error($message = '', $data = array()) {
        wp_send_json_error(array(
            'message' => $message,
            'data' => $data
        ));
    }

    /**
     * Verify nonce
     */
    public static function verify_nonce($nonce, $action = 'fluent_booking_admin_nonce') {
        if (!wp_verify_nonce($nonce, $action)) {
            self::send_error(__('Security check failed', 'fluent-booking'));
        }
    }

    /**
     * Check admin permission
     */
    public static function check_admin_permission() {
        if (!current_user_can('manage_options')) {
            self::send_error(__('You do not have permission to perform this action', 'fluent-booking'));
        }
    }

    /**
     * Get days of week
     */
    public static function get_days_of_week() {
        return array(
            0 => __('Sunday', 'fluent-booking'),
            1 => __('Monday', 'fluent-booking'),
            2 => __('Tuesday', 'fluent-booking'),
            3 => __('Wednesday', 'fluent-booking'),
            4 => __('Thursday', 'fluent-booking'),
            5 => __('Friday', 'fluent-booking'),
            6 => __('Saturday', 'fluent-booking')
        );
    }

    /**
     * Generate time slots
     */
    public static function generate_time_slots($start_time, $end_time, $duration = 30) {
        $slots = array();
        $start = strtotime($start_time);
        $end = strtotime($end_time);

        while ($start < $end) {
            $slots[] = date('H:i:s', $start);
            $start = strtotime('+' . $duration . ' minutes', $start);
        }

        return $slots;
    }
}
