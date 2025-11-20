<?php
/**
 * Database Management Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Database {

    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Forms table
        $forms_table = $wpdb->prefix . 'fluentbooking_forms';
        $forms_sql = "CREATE TABLE IF NOT EXISTS $forms_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'active',
            form_fields longtext,
            form_settings longtext,
            style_settings longtext,
            notification_settings longtext,
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Bookings table
        $bookings_table = $wpdb->prefix . 'fluentbooking_bookings';
        $bookings_sql = "CREATE TABLE IF NOT EXISTS $bookings_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            booking_end_time time,
            duration int(11) DEFAULT 30,
            status varchar(20) DEFAULT 'pending',
            form_data longtext,
            customer_notes text,
            admin_notes text,
            google_event_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY customer_email (customer_email)
        ) $charset_collate;";

        // Availability rules table
        $availability_table = $wpdb->prefix . 'fluentbooking_availability';
        $availability_sql = "CREATE TABLE IF NOT EXISTS $availability_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_available tinyint(1) DEFAULT 1,
            slot_duration int(11) DEFAULT 30,
            buffer_time int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";

        // Blocked dates table
        $blocked_dates_table = $wpdb->prefix . 'fluentbooking_blocked_dates';
        $blocked_dates_sql = "CREATE TABLE IF NOT EXISTS $blocked_dates_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            blocked_date date NOT NULL,
            blocked_from time,
            blocked_to time,
            reason varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY blocked_date (blocked_date)
        ) $charset_collate;";

        // Customers table
        $customers_table = $wpdb->prefix . 'fluentbooking_customers';
        $customers_sql = "CREATE TABLE IF NOT EXISTS $customers_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50),
            user_id bigint(20) UNSIGNED,
            total_bookings int(11) DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Email templates table
        $notifications_table = $wpdb->prefix . 'fluentbooking_notifications';
        $notifications_sql = "CREATE TABLE IF NOT EXISTS $notifications_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            notification_type varchar(50) NOT NULL,
            recipient_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";

        // Meta table for additional data
        $meta_table = $wpdb->prefix . 'fluentbooking_meta';
        $meta_sql = "CREATE TABLE IF NOT EXISTS $meta_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($forms_sql);
        dbDelta($bookings_sql);
        dbDelta($availability_sql);
        dbDelta($blocked_dates_sql);
        dbDelta($customers_sql);
        dbDelta($notifications_sql);
        dbDelta($meta_sql);
    }

    /**
     * Insert default data
     */
    public static function insert_default_data() {
        global $wpdb;

        $forms_table = $wpdb->prefix . 'fluentbooking_forms';

        // Check if default form exists
        $form_exists = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");

        if ($form_exists > 0) {
            return;
        }

        // Default form fields
        $default_fields = array(
            array(
                'id' => 'field_name',
                'type' => 'text',
                'label' => 'Full Name',
                'placeholder' => 'Enter your full name',
                'required' => true,
                'width' => '100',
                'order' => 1,
                'show_label' => true
            ),
            array(
                'id' => 'field_email',
                'type' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email',
                'required' => true,
                'width' => '50',
                'order' => 2,
                'show_label' => true
            ),
            array(
                'id' => 'field_phone',
                'type' => 'tel',
                'label' => 'Phone Number',
                'placeholder' => 'Enter your phone',
                'required' => false,
                'width' => '50',
                'order' => 3,
                'show_label' => true
            ),
            array(
                'id' => 'field_date',
                'type' => 'date',
                'label' => 'Select Date',
                'placeholder' => 'Choose appointment date',
                'required' => true,
                'width' => '50',
                'order' => 4,
                'show_label' => true
            ),
            array(
                'id' => 'field_time',
                'type' => 'time',
                'label' => 'Select Time',
                'placeholder' => 'Choose appointment time',
                'required' => true,
                'width' => '50',
                'order' => 5,
                'show_label' => true
            ),
            array(
                'id' => 'field_notes',
                'type' => 'textarea',
                'label' => 'Additional Notes',
                'placeholder' => 'Any special requests or notes',
                'required' => false,
                'width' => '100',
                'order' => 6,
                'show_label' => true
            )
        );

        // Default form settings
        $default_settings = array(
            'duration' => 30,
            'buffer_time' => 0,
            'min_booking_notice' => 24,
            'max_booking_advance' => 30,
            'confirmation_message' => 'Thank you! Your appointment has been booked successfully.',
            'redirect_url' => '',
            'success_action' => 'message'
        );

        // Default style settings
        $default_styles = array(
            'form_background' => '#ffffff',
            'form_border_color' => '#e0e0e0',
            'form_border_width' => '1',
            'form_border_radius' => '5',
            'form_padding' => '20',
            'label_color' => '#333333',
            'label_font_size' => '14',
            'label_font_weight' => '600',
            'label_font_family' => 'inherit',
            'field_background' => '#ffffff',
            'field_border_color' => '#cccccc',
            'field_border_width' => '1',
            'field_border_radius' => '3',
            'field_padding' => '10',
            'field_color' => '#333333',
            'field_font_size' => '14',
            'field_font_family' => 'inherit',
            'button_background' => '#0073aa',
            'button_color' => '#ffffff',
            'button_border_radius' => '3',
            'button_padding' => '12',
            'button_font_size' => '16',
            'button_font_weight' => '600'
        );

        // Default notification settings
        $default_notifications = array(
            'customer_notification' => array(
                'enabled' => true,
                'subject' => 'Appointment Confirmation - {{booking_date}}',
                'message' => 'Hi {{customer_name}},<br><br>Your appointment has been confirmed for {{booking_date}} at {{booking_time}}.<br><br>Thank you!'
            ),
            'admin_notification' => array(
                'enabled' => true,
                'subject' => 'New Appointment Booking',
                'message' => 'New appointment booking received from {{customer_name}} ({{customer_email}}) for {{booking_date}} at {{booking_time}}.'
            )
        );

        // Insert default form
        $wpdb->insert(
            $forms_table,
            array(
                'title' => 'Default Appointment Form',
                'description' => 'A simple appointment booking form',
                'status' => 'active',
                'form_fields' => wp_json_encode($default_fields),
                'form_settings' => wp_json_encode($default_settings),
                'style_settings' => wp_json_encode($default_styles),
                'notification_settings' => wp_json_encode($default_notifications),
                'created_by' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        $form_id = $wpdb->insert_id;

        // Insert default availability (Monday to Friday, 9 AM to 5 PM)
        $availability_table = $wpdb->prefix . 'fluentbooking_availability';
        $default_availability = array();

        for ($day = 1; $day <= 5; $day++) {
            $wpdb->insert(
                $availability_table,
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

        // Insert default email templates
        $notifications_table = $wpdb->prefix . 'fluentbooking_notifications';

        $wpdb->insert(
            $notifications_table,
            array(
                'form_id' => $form_id,
                'notification_type' => 'booking_confirmation',
                'recipient_type' => 'customer',
                'subject' => 'Appointment Confirmation - {{booking_date}}',
                'message' => '<h2>Appointment Confirmed</h2><p>Hi {{customer_name}},</p><p>Your appointment has been confirmed for <strong>{{booking_date}}</strong> at <strong>{{booking_time}}</strong>.</p><p>We look forward to seeing you!</p>',
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        $wpdb->insert(
            $notifications_table,
            array(
                'form_id' => $form_id,
                'notification_type' => 'booking_notification',
                'recipient_type' => 'admin',
                'subject' => 'New Appointment Booking',
                'message' => '<h2>New Booking Received</h2><p>New appointment booking from <strong>{{customer_name}}</strong> ({{customer_email}})</p><p><strong>Date:</strong> {{booking_date}}<br><strong>Time:</strong> {{booking_time}}</p><p><strong>Phone:</strong> {{customer_phone}}</p>',
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Drop all plugin tables
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'fluentbooking_forms',
            $wpdb->prefix . 'fluentbooking_bookings',
            $wpdb->prefix . 'fluentbooking_availability',
            $wpdb->prefix . 'fluentbooking_blocked_dates',
            $wpdb->prefix . 'fluentbooking_customers',
            $wpdb->prefix . 'fluentbooking_notifications',
            $wpdb->prefix . 'fluentbooking_meta'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}
