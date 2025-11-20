<?php
/**
 * Admin Menu Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Admin_Menu {

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
        add_action('admin_menu', array($this, 'register_menu'));
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __('Fluent Bookings', 'fluent-booking'),
            __('Fluent Bookings', 'fluent-booking'),
            'manage_options',
            'fluent-booking',
            array($this, 'render_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard
        add_submenu_page(
            'fluent-booking',
            __('Dashboard', 'fluent-booking'),
            __('Dashboard', 'fluent-booking'),
            'manage_options',
            'fluent-booking',
            array($this, 'render_dashboard_page')
        );

        // All Forms
        add_submenu_page(
            'fluent-booking',
            __('All Forms', 'fluent-booking'),
            __('All Forms', 'fluent-booking'),
            'manage_options',
            'fluent-booking-forms',
            array($this, 'render_forms_page')
        );

        // Add New Form
        add_submenu_page(
            'fluent-booking',
            __('Add New Form', 'fluent-booking'),
            __('Add New Form', 'fluent-booking'),
            'manage_options',
            'fluent-booking-add-form',
            array($this, 'render_add_form_page')
        );

        // Edit Form (hidden)
        add_submenu_page(
            null,
            __('Edit Form', 'fluent-booking'),
            __('Edit Form', 'fluent-booking'),
            'manage_options',
            'fluent-booking-edit-form',
            array($this, 'render_edit_form_page')
        );

        // Availability (hidden)
        add_submenu_page(
            null,
            __('Manage Availability', 'fluent-booking'),
            __('Manage Availability', 'fluent-booking'),
            'manage_options',
            'fluent-booking-availability',
            array($this, 'render_availability_page')
        );

        // All Bookings
        add_submenu_page(
            'fluent-booking',
            __('All Bookings', 'fluent-booking'),
            __('All Bookings', 'fluent-booking'),
            'manage_options',
            'fluent-booking-bookings',
            array($this, 'render_bookings_page')
        );

        // Calendar View
        add_submenu_page(
            'fluent-booking',
            __('Calendar', 'fluent-booking'),
            __('Calendar', 'fluent-booking'),
            'manage_options',
            'fluent-booking-calendar',
            array($this, 'render_calendar_page')
        );

        // Customers
        add_submenu_page(
            'fluent-booking',
            __('Customers', 'fluent-booking'),
            __('Customers', 'fluent-booking'),
            'manage_options',
            'fluent-booking-customers',
            array($this, 'render_customers_page')
        );

        // Settings
        add_submenu_page(
            'fluent-booking',
            __('Settings', 'fluent-booking'),
            __('Settings', 'fluent-booking'),
            'manage_options',
            'fluent-booking-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render forms page
     */
    public function render_forms_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/forms-list.php';
    }

    /**
     * Render add form page
     */
    public function render_add_form_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Render edit form page
     */
    public function render_edit_form_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Render bookings page
     */
    public function render_bookings_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/bookings-list.php';
    }

    /**
     * Render calendar page
     */
    public function render_calendar_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    /**
     * Render customers page
     */
    public function render_customers_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/customers.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render availability page
     */
    public function render_availability_page() {
        include FLUENT_BOOKING_PLUGIN_DIR . 'admin/views/availability.php';
    }
}
