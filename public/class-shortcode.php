<?php
/**
 * Shortcode Handler Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Shortcode {

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
        add_shortcode('fluent_booking', array($this, 'render_shortcode'));
    }

    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        $form_id = absint($atts['id']);

        if (!$form_id) {
            return '<p>' . __('Please provide a valid form ID', 'fluent-bookings') . '</p>';
        }

        // Get form data
        global $wpdb;
        $table = $wpdb->prefix . 'fluentbooking_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", $form_id), ARRAY_A);

        if (!$form) {
            return '<p>' . __('Form not found or inactive', 'fluent-bookings') . '</p>';
        }

        // Decode JSON fields
        $form['form_fields'] = json_decode($form['form_fields'], true);
        $form['form_settings'] = json_decode($form['form_settings'], true);
        $form['style_settings'] = json_decode($form['style_settings'], true);

        // Sort fields by order
        usort($form['form_fields'], function($a, $b) {
            return $a['order'] - $b['order'];
        });

        // Start output buffering
        ob_start();

        // Render form
        $this->render_form($form);

        return ob_get_clean();
    }

    /**
     * Render form
     */
    private function render_form($form) {
        $form_id = $form['id'];
        $fields = $form['form_fields'];
        $settings = $form['form_settings'];
        $styles = $form['style_settings'];

        // Generate unique ID for this form instance
        $instance_id = 'fb-form-' . $form_id . '-' . uniqid();

        ?>
        <div class="fluent-booking-form-wrapper" id="<?php echo esc_attr($instance_id); ?>" data-form-id="<?php echo esc_attr($form_id); ?>">
            <?php $this->render_inline_styles($instance_id, $styles); ?>

            <form class="fluent-booking-form" method="post">
                <?php if (!empty($form['description'])) : ?>
                    <div class="fb-form-description">
                        <?php echo wp_kses_post($form['description']); ?>
                    </div>
                <?php endif; ?>

                <div class="fb-form-fields">
                    <?php foreach ($fields as $field) : ?>
                        <?php $this->render_field($field); ?>
                    <?php endforeach; ?>
                </div>

                <div class="fb-form-footer">
                    <button type="submit" class="fb-submit-button">
                        <?php esc_html_e('Book Appointment', 'fluent-bookings'); ?>
                    </button>
                </div>

                <div class="fb-form-message" style="display: none;"></div>

                <input type="hidden" name="action" value="fb_submit_booking">
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                <?php wp_nonce_field('fluent_booking_public_nonce', 'fb_nonce'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render form field
     */
    private function render_field($field) {
        $field_id = esc_attr($field['id']);
        $field_type = esc_attr($field['type']);
        $field_label = esc_html($field['label']);
        $field_placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
        $field_required = isset($field['required']) && $field['required'] ? 'required' : '';
        $field_width = isset($field['width']) ? absint($field['width']) : 100;
        $show_label = isset($field['show_label']) ? $field['show_label'] : true;

        ?>
        <div class="fb-field-wrapper fb-field-<?php echo $field_type; ?>" style="width: <?php echo $field_width; ?>%;">
            <?php if ($show_label) : ?>
                <label for="<?php echo $field_id; ?>" class="fb-field-label">
                    <?php echo $field_label; ?>
                    <?php if ($field_required) : ?>
                        <span class="fb-required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <div class="fb-field-input">
                <?php
                switch ($field_type) {
                    case 'textarea':
                        ?>
                        <textarea
                            id="<?php echo $field_id; ?>"
                            name="<?php echo $field_id; ?>"
                            placeholder="<?php echo $field_placeholder; ?>"
                            <?php echo $field_required; ?>
                            rows="4"
                        ></textarea>
                        <?php
                        break;

                    case 'select':
                        ?>
                        <select
                            id="<?php echo $field_id; ?>"
                            name="<?php echo $field_id; ?>"
                            <?php echo $field_required; ?>
                        >
                            <option value=""><?php echo $field_placeholder; ?></option>
                            <?php if (!empty($field['options'])) : ?>
                                <?php foreach ($field['options'] as $option) : ?>
                                    <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php
                        break;

                    case 'radio':
                        if (!empty($field['options'])) :
                            foreach ($field['options'] as $index => $option) :
                                ?>
                                <label class="fb-radio-label">
                                    <input
                                        type="radio"
                                        name="<?php echo $field_id; ?>"
                                        value="<?php echo esc_attr($option); ?>"
                                        <?php echo $field_required; ?>
                                    >
                                    <?php echo esc_html($option); ?>
                                </label>
                                <?php
                            endforeach;
                        endif;
                        break;

                    case 'checkbox':
                        if (!empty($field['options'])) :
                            foreach ($field['options'] as $index => $option) :
                                ?>
                                <label class="fb-checkbox-label">
                                    <input
                                        type="checkbox"
                                        name="<?php echo $field_id; ?>[]"
                                        value="<?php echo esc_attr($option); ?>"
                                    >
                                    <?php echo esc_html($option); ?>
                                </label>
                                <?php
                            endforeach;
                        endif;
                        break;

                    case 'date':
                        ?>
                        <input
                            type="date"
                            id="<?php echo $field_id; ?>"
                            name="<?php echo $field_id; ?>"
                            placeholder="<?php echo $field_placeholder; ?>"
                            <?php echo $field_required; ?>
                            class="fb-date-field"
                            min="<?php echo date('Y-m-d'); ?>"
                        >
                        <?php
                        break;

                    case 'time':
                        ?>
                        <select
                            id="<?php echo $field_id; ?>"
                            name="<?php echo $field_id; ?>"
                            <?php echo $field_required; ?>
                            class="fb-time-field"
                            disabled
                        >
                            <option value=""><?php esc_html_e('Select date first', 'fluent-bookings'); ?></option>
                        </select>
                        <?php
                        break;

                    default:
                        ?>
                        <input
                            type="<?php echo $field_type; ?>"
                            id="<?php echo $field_id; ?>"
                            name="<?php echo $field_id; ?>"
                            placeholder="<?php echo $field_placeholder; ?>"
                            <?php echo $field_required; ?>
                        >
                        <?php
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_inline_styles($instance_id, $styles) {
        ?>
        <style>
            #<?php echo $instance_id; ?> .fluent-booking-form {
                background-color: <?php echo esc_attr($styles['form_background']); ?>;
                border: <?php echo esc_attr($styles['form_border_width']); ?>px solid <?php echo esc_attr($styles['form_border_color']); ?>;
                border-radius: <?php echo esc_attr($styles['form_border_radius']); ?>px;
                padding: <?php echo esc_attr($styles['form_padding']); ?>px;
            }

            #<?php echo $instance_id; ?> .fb-field-label {
                color: <?php echo esc_attr($styles['label_color']); ?>;
                font-size: <?php echo esc_attr($styles['label_font_size']); ?>px;
                font-weight: <?php echo esc_attr($styles['label_font_weight']); ?>;
                font-family: <?php echo esc_attr($styles['label_font_family']); ?>;
            }

            #<?php echo $instance_id; ?> .fb-field-input input,
            #<?php echo $instance_id; ?> .fb-field-input select,
            #<?php echo $instance_id; ?> .fb-field-input textarea {
                background-color: <?php echo esc_attr($styles['field_background']); ?>;
                border: <?php echo esc_attr($styles['field_border_width']); ?>px solid <?php echo esc_attr($styles['field_border_color']); ?>;
                border-radius: <?php echo esc_attr($styles['field_border_radius']); ?>px;
                padding: <?php echo esc_attr($styles['field_padding']); ?>px;
                color: <?php echo esc_attr($styles['field_color']); ?>;
                font-size: <?php echo esc_attr($styles['field_font_size']); ?>px;
                font-family: <?php echo esc_attr($styles['field_font_family']); ?>;
            }

            #<?php echo $instance_id; ?> .fb-submit-button {
                background-color: <?php echo esc_attr($styles['button_background']); ?>;
                color: <?php echo esc_attr($styles['button_color']); ?>;
                border-radius: <?php echo esc_attr($styles['button_border_radius']); ?>px;
                padding: <?php echo esc_attr($styles['button_padding']); ?>px;
                font-size: <?php echo esc_attr($styles['button_font_size']); ?>px;
                font-weight: <?php echo esc_attr($styles['button_font_weight']); ?>;
            }
        </style>
        <?php
    }
}
