<?php
/**
 * Availability Management Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Availability {

    /**
     * Get available slots for a specific date
     */
    public static function get_available_slots($form_id, $date) {
        global $wpdb;

        // Get day of week (0 = Sunday, 6 = Saturday)
        $day_of_week = date('w', strtotime($date));

        // Get availability rules for this day
        $availability_table = $wpdb->prefix . 'fluentbooking_availability';
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table
            WHERE form_id = %d
            AND day_of_week = %d
            AND is_available = 1",
            $form_id,
            $day_of_week
        ));

        if (empty($rules)) {
            return array();
        }

        $all_slots = array();

        // Generate time slots for each rule
        foreach ($rules as $rule) {
            $slots = Fluent_Booking_Helper::generate_time_slots(
                $rule->start_time,
                $rule->end_time,
                $rule->slot_duration
            );

            $all_slots = array_merge($all_slots, $slots);
        }

        // Remove duplicate slots
        $all_slots = array_unique($all_slots);

        // Remove booked slots
        $booked_slots = self::get_booked_slots($form_id, $date);
        $available_slots = array_diff($all_slots, $booked_slots);

        // Remove blocked slots
        $blocked_slots = self::get_blocked_slots($form_id, $date);
        $available_slots = array_diff($available_slots, $blocked_slots);

        // Sort slots
        sort($available_slots);

        return array_values($available_slots);
    }

    /**
     * Get booked slots for a specific date
     */
    private static function get_booked_slots($form_id, $date) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_time FROM $table
            WHERE form_id = %d
            AND booking_date = %s
            AND status NOT IN ('cancelled')",
            $form_id,
            $date
        ));

        $booked_slots = array();
        foreach ($bookings as $booking) {
            $booked_slots[] = $booking->booking_time;
        }

        return $booked_slots;
    }

    /**
     * Get blocked slots for a specific date
     */
    private static function get_blocked_slots($form_id, $date) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_blocked_dates';

        $blocked = $wpdb->get_results($wpdb->prepare(
            "SELECT blocked_from, blocked_to FROM $table
            WHERE form_id = %d
            AND blocked_date = %s",
            $form_id,
            $date
        ));

        $blocked_slots = array();

        foreach ($blocked as $block) {
            if (empty($block->blocked_from) && empty($block->blocked_to)) {
                // Entire day is blocked
                return self::get_all_possible_slots($form_id, $date);
            }

            // Generate slots in blocked time range
            if (!empty($block->blocked_from) && !empty($block->blocked_to)) {
                $slots = Fluent_Booking_Helper::generate_time_slots(
                    $block->blocked_from,
                    $block->blocked_to,
                    30
                );
                $blocked_slots = array_merge($blocked_slots, $slots);
            }
        }

        return $blocked_slots;
    }

    /**
     * Get all possible slots for a date (used when day is fully blocked)
     */
    private static function get_all_possible_slots($form_id, $date) {
        $day_of_week = date('w', strtotime($date));
        global $wpdb;

        $availability_table = $wpdb->prefix . 'fluentbooking_availability';
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table
            WHERE form_id = %d
            AND day_of_week = %d",
            $form_id,
            $day_of_week
        ));

        $all_slots = array();

        foreach ($rules as $rule) {
            $slots = Fluent_Booking_Helper::generate_time_slots(
                $rule->start_time,
                $rule->end_time,
                $rule->slot_duration
            );
            $all_slots = array_merge($all_slots, $slots);
        }

        return array_unique($all_slots);
    }

    /**
     * Check if a specific date is available
     */
    public static function is_date_available($form_id, $date) {
        $slots = self::get_available_slots($form_id, $date);
        return !empty($slots);
    }

    /**
     * Get availability rules for a form
     */
    public static function get_rules($form_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_availability';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE form_id = %d ORDER BY day_of_week, start_time",
            $form_id
        ), ARRAY_A);
    }

    /**
     * Save availability rules
     */
    public static function save_rules($form_id, $rules) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_availability';

        // Delete existing rules
        $wpdb->delete($table, array('form_id' => $form_id), array('%d'));

        // Insert new rules
        foreach ($rules as $rule) {
            $wpdb->insert(
                $table,
                array(
                    'form_id' => $form_id,
                    'day_of_week' => absint($rule['day_of_week']),
                    'start_time' => sanitize_text_field($rule['start_time']),
                    'end_time' => sanitize_text_field($rule['end_time']),
                    'is_available' => isset($rule['is_available']) ? absint($rule['is_available']) : 1,
                    'slot_duration' => isset($rule['slot_duration']) ? absint($rule['slot_duration']) : 30,
                    'buffer_time' => isset($rule['buffer_time']) ? absint($rule['buffer_time']) : 0
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%d')
            );
        }

        return true;
    }

    /**
     * Block a specific date
     */
    public static function block_date($form_id, $date, $from = null, $to = null, $reason = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_blocked_dates';

        return $wpdb->insert(
            $table,
            array(
                'form_id' => $form_id,
                'blocked_date' => $date,
                'blocked_from' => $from,
                'blocked_to' => $to,
                'reason' => $reason
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Unblock a specific date
     */
    public static function unblock_date($block_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_blocked_dates';

        return $wpdb->delete($table, array('id' => $block_id), array('%d'));
    }

    /**
     * Get blocked dates for a form
     */
    public static function get_blocked_dates($form_id, $date_from = '', $date_to = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_blocked_dates';

        $where = array('form_id = %d');
        $values = array($form_id);

        if (!empty($date_from)) {
            $where[] = 'blocked_date >= %s';
            $values[] = $date_from;
        }

        if (!empty($date_to)) {
            $where[] = 'blocked_date <= %s';
            $values[] = $date_to;
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY blocked_date";
        $sql = $wpdb->prepare($sql, $values);

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
