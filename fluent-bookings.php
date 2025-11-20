<?php
/**
 * Plugin Name: Fluent Appointments
 * Plugin URI: https://github.com/akashsahay1/fluent-bookings
 * Description: A powerful appointment booking plugin with Google Calendar integration, drag-and-drop form builder, and smart availability management.
 * Version: 1.0.0
 * Author: Akash
 * Author URI: https://github.com/akashsahay1
 * Text Domain: fluent-booking
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FLUENT_BOOKING_VERSION', '1.0.0');
define('FLUENT_BOOKING_PLUGIN_FILE', __FILE__);
define('FLUENT_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLUENT_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLUENT_BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Fluent Booking Class
 */
final class Fluent_Booking {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-database.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-booking.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-email.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-availability.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-helper.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'includes/class-google-calendar.php';

        // Admin includes
        if (is_admin()) {
            require_once FLUENT_BOOKING_PLUGIN_DIR . 'admin/class-admin-menu.php';
            require_once FLUENT_BOOKING_PLUGIN_DIR . 'admin/class-form-builder.php';
            require_once FLUENT_BOOKING_PLUGIN_DIR . 'admin/class-booking-manager.php';
        }

        // Public includes
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'public/class-shortcode.php';
        require_once FLUENT_BOOKING_PLUGIN_DIR . 'public/class-form-handler.php';

        // Integrations
        if (file_exists(FLUENT_BOOKING_PLUGIN_DIR . 'integrations/elementor/class-elementor-widget.php')) {
            require_once FLUENT_BOOKING_PLUGIN_DIR . 'integrations/elementor/class-elementor-widget.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Init hook
        add_action('init', array($this, 'init'));

        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'public_enqueue_scripts'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        Fluent_Booking_Database::create_tables();
        Fluent_Booking_Database::insert_default_data();

        // Set default options
        if (!get_option('fluent_booking_version')) {
            add_option('fluent_booking_version', FLUENT_BOOKING_VERSION);
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize classes
        if (is_admin()) {
            Fluent_Booking_Admin_Menu::instance();
            Fluent_Booking_Form_Builder::instance();
            Fluent_Booking_Booking_Manager::instance();
        }

        Fluent_Booking_Shortcode::instance();
        Fluent_Booking_Form_Handler::instance();
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('fluent-booking', false, dirname(FLUENT_BOOKING_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'fluent-booking') === false) {
            return;
        }

        // CSS
        wp_enqueue_style('fluent-booking-admin', FLUENT_BOOKING_PLUGIN_URL . 'admin/assets/css/admin.css', array(), FLUENT_BOOKING_VERSION);
        wp_enqueue_style('fluent-booking-form-builder', FLUENT_BOOKING_PLUGIN_URL . 'admin/assets/css/form-builder.css', array(), FLUENT_BOOKING_VERSION);

        // jQuery UI for drag and drop
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Admin JS
        wp_enqueue_script('fluent-booking-admin', FLUENT_BOOKING_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), FLUENT_BOOKING_VERSION, true);
        wp_enqueue_script('fluent-booking-form-builder', FLUENT_BOOKING_PLUGIN_URL . 'admin/assets/js/form-builder.js', array('jquery', 'jquery-ui-sortable'), FLUENT_BOOKING_VERSION, true);

        // Localize script
        wp_localize_script('fluent-booking-admin', 'fluentBookingAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluent_booking_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'fluent-booking'),
                'save_success' => __('Saved successfully!', 'fluent-booking'),
                'save_error' => __('Error saving. Please try again.', 'fluent-booking'),
            )
        ));
    }

    /**
     * Enqueue public scripts and styles
     */
    public function public_enqueue_scripts() {
        // CSS
        wp_enqueue_style('fluent-booking-public', FLUENT_BOOKING_PLUGIN_URL . 'public/assets/css/public.css', array(), FLUENT_BOOKING_VERSION);

        // JS
        wp_enqueue_script('fluent-booking-public', FLUENT_BOOKING_PLUGIN_URL . 'public/assets/js/public.js', array('jquery'), FLUENT_BOOKING_VERSION, true);

        // Localize script
        wp_localize_script('fluent-booking-public', 'fluentBookingPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluent_booking_public_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'fluent-booking'),
                'select_date' => __('Please select a date', 'fluent-booking'),
                'select_time' => __('Please select a time', 'fluent-booking'),
                'fill_required' => __('Please fill all required fields', 'fluent-booking'),
            )
        ));
    }
}

/**
 * Initialize the plugin
 */
function fluent_booking() {
    return Fluent_Booking::instance();
}

// Kickoff
fluent_booking();
