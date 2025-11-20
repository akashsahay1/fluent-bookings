<?php
/**
 * Email Management Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Email {

    /**
     * Send booking notifications
     */
    public static function send_booking_notifications($booking_id) {
        $booking = Fluent_Booking::get($booking_id);

        if (!$booking) {
            return false;
        }

        // Send customer notification
        self::send_customer_notification($booking);

        // Send admin notification
        self::send_admin_notification($booking);

        return true;
    }

    /**
     * Send customer notification
     */
    public static function send_customer_notification($booking) {
        global $wpdb;

        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fluentbooking_notifications
            WHERE form_id = %d
            AND notification_type = 'booking_confirmation'
            AND recipient_type = 'customer'
            AND status = 'active'
            LIMIT 1",
            $booking['form_id']
        ));

        if (!$notification) {
            return false;
        }

        $to = $booking['customer_email'];
        $subject = Fluent_Booking_Helper::parse_merge_tags($notification->subject, $booking);
        $message = Fluent_Booking_Helper::parse_merge_tags($notification->message, $booking);

        return self::send_email($to, $subject, $message);
    }

    /**
     * Send admin notification
     */
    public static function send_admin_notification($booking) {
        global $wpdb;

        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fluentbooking_notifications
            WHERE form_id = %d
            AND notification_type = 'booking_notification'
            AND recipient_type = 'admin'
            AND status = 'active'
            LIMIT 1",
            $booking['form_id']
        ));

        if (!$notification) {
            return false;
        }

        $to = get_option('admin_email');
        $subject = Fluent_Booking_Helper::parse_merge_tags($notification->subject, $booking);
        $message = Fluent_Booking_Helper::parse_merge_tags($notification->message, $booking);

        return self::send_email($to, $subject, $message);
    }

    /**
     * Send email
     */
    public static function send_email($to, $subject, $message, $headers = array()) {
        if (empty($headers)) {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );
        }

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template
     */
    public static function get_email_template($content) {
        $template = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #0073aa;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .email-body {
            padding: 30px;
        }
        .email-footer {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        h2 {
            margin-top: 0;
            color: #0073aa;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0073aa;
            color: #ffffff;
            text-decoration: none;
            border-radius: 3px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>' . get_bloginfo('name') . '</h1>
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p>&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.</p>
            <p>' . get_site_url() . '</p>
        </div>
    </div>
</body>
</html>';

        return $template;
    }
}
