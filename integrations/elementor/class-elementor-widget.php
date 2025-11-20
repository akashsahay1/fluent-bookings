<?php
/**
 * Elementor Widget for Fluent Bookings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'fluent-booking';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Fluent Booking Form', 'fluent-booking');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return array('general');
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Form Settings', 'fluent-booking'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        // Get all forms
        global $wpdb;
        $forms = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}fluentbooking_forms WHERE status = 'active' ORDER BY title ASC",
            ARRAY_A
        );

        $form_options = array();
        foreach ($forms as $form) {
            $form_options[$form['id']] = $form['title'];
        }

        $this->add_control(
            'form_id',
            array(
                'label' => __('Select Form', 'fluent-booking'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $form_options,
                'default' => !empty($form_options) ? array_key_first($form_options) : ''
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $form_id = $settings['form_id'];

        if (!empty($form_id)) {
            echo do_shortcode('[fluent_booking id="' . absint($form_id) . '"]');
        } else {
            echo '<p>' . __('Please select a form', 'fluent-booking') . '</p>';
        }
    }
}

/**
 * Register Elementor Widget
 */
function fluent_booking_register_elementor_widget() {
    if (did_action('elementor/loaded')) {
        \Elementor\Plugin::instance()->widgets_manager->register(new Fluent_Booking_Elementor_Widget());
    }
}
add_action('elementor/widgets/register', 'fluent_booking_register_elementor_widget');
