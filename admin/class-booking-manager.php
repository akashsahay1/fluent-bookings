<?php
/**
 * Booking Manager Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Booking_Manager {

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
        add_action('wp_ajax_fb_get_bookings', array($this, 'get_bookings'));
        add_action('wp_ajax_fb_update_booking_status', array($this, 'update_booking_status'));
        add_action('wp_ajax_fb_delete_booking', array($this, 'delete_booking'));
        add_action('wp_ajax_fb_get_calendar_bookings', array($this, 'get_calendar_bookings'));
        add_action('wp_ajax_fb_get_booking_details', array($this, 'get_booking_details'));
        add_action('wp_ajax_fb_get_customer_bookings', array($this, 'get_customer_bookings'));
        add_action('wp_ajax_fb_export_bookings', array($this, 'export_bookings'));
        add_action('wp_ajax_fb_export_customers', array($this, 'export_customers'));
    }

    /**
     * Get bookings
     */
    public function get_bookings() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $args = array(
            'form_id' => isset($_POST['form_id']) ? absint($_POST['form_id']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
            'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'orderby' => isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'created_at',
            'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC',
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 20,
            'offset' => isset($_POST['offset']) ? absint($_POST['offset']) : 0
        );

        $bookings = Fluent_Booking::get_all($args);
        $total = Fluent_Booking::get_count($args);

        Fluent_Booking_Helper::send_success('', array(
            'bookings' => $bookings,
            'total' => $total
        ));
    }

    /**
     * Update booking status
     */
    public function update_booking_status() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$booking_id || !in_array($status, array('pending', 'confirmed', 'cancelled', 'completed'))) {
            Fluent_Booking_Helper::send_error(__('Invalid parameters', 'fluent-booking'));
        }

        $result = Fluent_Booking::update($booking_id, array('status' => $status));

        if ($result) {
            Fluent_Booking_Helper::send_success(__('Status updated successfully', 'fluent-booking'));
        } else {
            Fluent_Booking_Helper::send_error(__('Failed to update status', 'fluent-booking'));
        }
    }

    /**
     * Delete booking
     */
    public function delete_booking() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;

        if (!$booking_id) {
            Fluent_Booking_Helper::send_error(__('Invalid booking ID', 'fluent-booking'));
        }

        $result = Fluent_Booking::delete($booking_id);

        if ($result) {
            Fluent_Booking_Helper::send_success(__('Booking deleted successfully', 'fluent-booking'));
        } else {
            Fluent_Booking_Helper::send_error(__('Failed to delete booking', 'fluent-booking'));
        }
    }

    /**
     * Get calendar bookings
     */
    public function get_calendar_bookings() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $month = isset($_POST['month']) ? absint($_POST['month']) : date('m');
        $year = isset($_POST['year']) ? absint($_POST['year']) : date('Y');
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : sprintf('%04d-%02d-01', $year, $month);
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : date('Y-m-t', strtotime($date_from));

        $args = array(
            'form_id' => $form_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'limit' => 1000,
            'offset' => 0
        );

        $bookings = Fluent_Booking::get_all($args);

        // Format bookings for calendar
        $calendar_events = array();
        foreach ($bookings as $booking) {
            $calendar_events[] = array(
                'id' => $booking['id'],
                'title' => $booking['customer_name'],
                'start' => $booking['booking_date'] . 'T' . $booking['booking_time'],
                'end' => $booking['booking_date'] . 'T' . $booking['booking_end_time'],
                'status' => $booking['status'],
                'email' => $booking['customer_email'],
                'phone' => $booking['customer_phone']
            );
        }

        Fluent_Booking_Helper::send_success('', $calendar_events);
    }

    /**
     * Get booking details
     */
    public function get_booking_details() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;

        if (!$booking_id) {
            Fluent_Booking_Helper::send_error(__('Invalid booking ID', 'fluent-booking'));
        }

        $booking = Fluent_Booking::get($booking_id);

        if (!$booking) {
            Fluent_Booking_Helper::send_error(__('Booking not found', 'fluent-booking'));
        }

        Fluent_Booking_Helper::send_success('', $booking);
    }

    /**
     * Get customer bookings
     */
    public function get_customer_bookings() {
        Fluent_Booking_Helper::verify_nonce($_POST['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';

        if (!$customer_email) {
            Fluent_Booking_Helper::send_error(__('Invalid customer email', 'fluent-booking'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_bookings';

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE customer_email = %s ORDER BY booking_date DESC, booking_time DESC",
            $customer_email
        ), ARRAY_A);

        Fluent_Booking_Helper::send_success('', $bookings);
    }

    /**
     * Export bookings
     */
    public function export_bookings() {
        Fluent_Booking_Helper::verify_nonce($_GET['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        $args = array(
            'form_id' => isset($_GET['form_id']) ? absint($_GET['form_id']) : 0,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'limit' => 10000,
            'offset' => 0
        );

        $bookings = Fluent_Booking::get_all($args);

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, array(
            'ID',
            'Customer Name',
            'Email',
            'Phone',
            'Date',
            'Time',
            'Duration',
            'Status',
            'Created At'
        ));

        // CSV data
        foreach ($bookings as $booking) {
            fputcsv($output, array(
                $booking['id'],
                $booking['customer_name'],
                $booking['customer_email'],
                $booking['customer_phone'],
                Fluent_Booking_Helper::format_date($booking['booking_date']),
                Fluent_Booking_Helper::format_time($booking['booking_time']),
                $booking['duration'] . ' min',
                $booking['status'],
                $booking['created_at']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export customers
     */
    public function export_customers() {
        Fluent_Booking_Helper::verify_nonce($_GET['nonce']);
        Fluent_Booking_Helper::check_admin_permission();

        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_customers';

        $customers = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC",
            ARRAY_A
        );

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=customers-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, array(
            'ID',
            'Name',
            'Email',
            'Phone',
            'Total Bookings',
            'Registered Date'
        ));

        // CSV data
        foreach ($customers as $customer) {
            fputcsv($output, array(
                $customer['id'],
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $customer['total_bookings'],
                $customer['created_at']
            ));
        }

        fclose($output);
        exit;
    }
}
