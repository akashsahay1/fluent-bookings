<?php
/**
 * Booking Management Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking {

    /**
     * Create new booking
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        // Validate required fields
        $required = array('form_id', 'customer_name', 'customer_email', 'booking_date', 'booking_time');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                /* translators: %s: Field name */
                return new WP_Error('missing_field', sprintf(__('Field %s is required', 'fluent-booking'), $field));
            }
        }

        // Check if slot is available
        if (!self::is_slot_available($data['form_id'], $data['booking_date'], $data['booking_time'])) {
            return new WP_Error('slot_unavailable', __('This time slot is not available', 'fluent-booking'));
        }

        // Calculate end time
        $duration = isset($data['duration']) ? absint($data['duration']) : 30;
        $booking_end_time = date('H:i:s', strtotime($data['booking_time']) + ($duration * 60));

        // Prepare booking data
        $booking_data = array(
            'form_id' => absint($data['form_id']),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
            'booking_date' => sanitize_text_field($data['booking_date']),
            'booking_time' => sanitize_text_field($data['booking_time']),
            'booking_end_time' => $booking_end_time,
            'duration' => $duration,
            'status' => 'pending',
            'form_data' => isset($data['form_data']) ? wp_json_encode($data['form_data']) : '',
            'customer_notes' => isset($data['customer_notes']) ? sanitize_textarea_field($data['customer_notes']) : ''
        );

        // Insert booking
        $result = $wpdb->insert($table, $booking_data);

        if ($result === false) {
            return new WP_Error('insert_failed', __('Failed to create booking', 'fluent-booking'));
        }

        $booking_id = $wpdb->insert_id;

        // Update or create customer
        self::update_customer($data);

        // Send notifications
        Fluent_Booking_Email::send_booking_notifications($booking_id);

        return $booking_id;
    }

    /**
     * Update customer record
     */
    private static function update_customer($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_customers';

        $email = sanitize_email($data['customer_email']);
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s",
            $email
        ));

        if ($customer) {
            // Update existing customer
            $wpdb->update(
                $table,
                array('total_bookings' => $customer->total_bookings + 1),
                array('id' => $customer->id),
                array('%d'),
                array('%d')
            );
        } else {
            // Create new customer
            $wpdb->insert(
                $table,
                array(
                    'name' => sanitize_text_field($data['customer_name']),
                    'email' => $email,
                    'phone' => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
                    'total_bookings' => 1
                ),
                array('%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Check if time slot is available
     */
    public static function is_slot_available($form_id, $date, $time) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        // Check for existing bookings
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE form_id = %d
            AND booking_date = %s
            AND booking_time = %s
            AND status NOT IN ('cancelled')",
            $form_id,
            $date,
            $time
        ));

        if ($existing > 0) {
            return false;
        }

        // Check if date is blocked
        $blocked_table = $wpdb->prefix . 'fluentbooking_blocked_dates';
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $blocked_table
            WHERE form_id = %d
            AND blocked_date = %s
            AND (blocked_from IS NULL OR %s >= blocked_from)
            AND (blocked_to IS NULL OR %s <= blocked_to)",
            $form_id,
            $date,
            $time,
            $time
        ));

        if ($blocked > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get booking by ID
     */
    public static function get($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);

        if ($booking && !empty($booking['form_data'])) {
            $booking['form_data'] = json_decode($booking['form_data'], true);
        }

        return $booking;
    }

    /**
     * Update booking
     */
    public static function update($booking_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $update_data = array();

        $allowed_fields = array('status', 'booking_date', 'booking_time', 'admin_notes', 'customer_notes');

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $booking_id),
            null,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete booking
     */
    public static function delete($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        return $wpdb->delete($table, array('id' => $booking_id), array('%d'));
    }

    /**
     * Get all bookings with filters
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $defaults = array(
            'form_id' => 0,
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $where_values = array();

        if (!empty($args['form_id'])) {
            $where[] = 'form_id = %d';
            $where_values[] = $args['form_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'booking_date >= %s';
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'booking_date <= %s';
            $where_values[] = $args['date_to'];
        }

        if (!empty($args['search'])) {
            $where[] = '(customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get bookings count
     */
    public static function get_count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $where = array('1=1');
        $where_values = array();

        if (!empty($args['form_id'])) {
            $where[] = 'form_id = %d';
            $where_values[] = $args['form_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'booking_date >= %s';
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'booking_date <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_var($sql);
    }
}
