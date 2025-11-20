<?php
/**
 * Google Calendar Integration Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Fluent_Booking_Google_Calendar {

    /**
     * Google OAuth URL
     */
    const OAUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const CALENDAR_API_URL = 'https://www.googleapis.com/calendar/v3';

    /**
     * Get authorization URL
     */
    public static function get_auth_url() {
        $client_id = get_option('fluent_booking_google_client_id');
        $redirect_uri = admin_url('admin.php?page=fluent-booking-settings&tab=google-calendar&action=callback');

        if (empty($client_id)) {
            return false;
        }

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent'
        );

        return self::OAUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public static function exchange_code($code) {
        $client_id = get_option('fluent_booking_google_client_id');
        $client_secret = get_option('fluent_booking_google_client_secret');
        $redirect_uri = admin_url('admin.php?page=fluent-booking-settings&tab=google-calendar&action=callback');

        $response = wp_remote_post(self::TOKEN_URL, array(
            'body' => array(
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            update_option('fluent_booking_google_access_token', $body['access_token']);

            if (isset($body['refresh_token'])) {
                update_option('fluent_booking_google_refresh_token', $body['refresh_token']);
            }

            update_option('fluent_booking_google_token_expires', time() + $body['expires_in']);

            return true;
        }

        return new WP_Error('token_error', 'Failed to get access token');
    }

    /**
     * Refresh access token
     */
    public static function refresh_token() {
        $refresh_token = get_option('fluent_booking_google_refresh_token');
        $client_id = get_option('fluent_booking_google_client_id');
        $client_secret = get_option('fluent_booking_google_client_secret');

        if (empty($refresh_token)) {
            return false;
        }

        $response = wp_remote_post(self::TOKEN_URL, array(
            'body' => array(
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'refresh_token'
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            update_option('fluent_booking_google_access_token', $body['access_token']);
            update_option('fluent_booking_google_token_expires', time() + $body['expires_in']);
            return true;
        }

        return false;
    }

    /**
     * Get valid access token
     */
    private static function get_access_token() {
        $access_token = get_option('fluent_booking_google_access_token');
        $expires = get_option('fluent_booking_google_token_expires', 0);

        // Refresh if expired
        if (time() >= $expires - 300) {
            self::refresh_token();
            $access_token = get_option('fluent_booking_google_access_token');
        }

        return $access_token;
    }

    /**
     * Create calendar event
     */
    public static function create_event($booking_data, $calendar_id = 'primary') {
        $access_token = self::get_access_token();

        if (empty($access_token)) {
            return new WP_Error('no_token', 'No access token available');
        }

        $start_datetime = $booking_data['booking_date'] . 'T' . $booking_data['booking_time'];
        $end_datetime = $booking_data['booking_date'] . 'T' . $booking_data['booking_end_time'];

        $event = array(
            'summary' => 'Appointment with ' . $booking_data['customer_name'],
            'description' => 'Email: ' . $booking_data['customer_email'] . "\n" .
                           'Phone: ' . ($booking_data['customer_phone'] ?? 'N/A') . "\n" .
                           'Notes: ' . ($booking_data['customer_notes'] ?? ''),
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'attendees' => array(
                array('email' => $booking_data['customer_email'])
            ),
            'reminders' => array(
                'useDefault' => false,
                'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 60)
                )
            )
        );

        $response = wp_remote_post(
            self::CALENDAR_API_URL . '/calendars/' . urlencode($calendar_id) . '/events',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($event)
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['id'])) {
            return $body['id'];
        }

        return new WP_Error('create_event_failed', 'Failed to create calendar event');
    }

    /**
     * Update calendar event
     */
    public static function update_event($event_id, $booking_data, $calendar_id = 'primary') {
        $access_token = self::get_access_token();

        if (empty($access_token) || empty($event_id)) {
            return false;
        }

        $start_datetime = $booking_data['booking_date'] . 'T' . $booking_data['booking_time'];
        $end_datetime = $booking_data['booking_date'] . 'T' . $booking_data['booking_end_time'];

        $event = array(
            'summary' => 'Appointment with ' . $booking_data['customer_name'],
            'description' => 'Email: ' . $booking_data['customer_email'] . "\n" .
                           'Phone: ' . ($booking_data['customer_phone'] ?? 'N/A') . "\n" .
                           'Notes: ' . ($booking_data['customer_notes'] ?? ''),
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => wp_timezone_string()
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => wp_timezone_string()
            )
        );

        $response = wp_remote_request(
            self::CALENDAR_API_URL . '/calendars/' . urlencode($calendar_id) . '/events/' . urlencode($event_id),
            array(
                'method' => 'PUT',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($event)
            )
        );

        return !is_wp_error($response);
    }

    /**
     * Delete calendar event
     */
    public static function delete_event($event_id, $calendar_id = 'primary') {
        $access_token = self::get_access_token();

        if (empty($access_token) || empty($event_id)) {
            return false;
        }

        $response = wp_remote_request(
            self::CALENDAR_API_URL . '/calendars/' . urlencode($calendar_id) . '/events/' . urlencode($event_id),
            array(
                'method' => 'DELETE',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                )
            )
        );

        return !is_wp_error($response);
    }

    /**
     * Get user calendars list
     */
    public static function get_calendars() {
        $access_token = self::get_access_token();

        if (empty($access_token)) {
            return array();
        }

        $response = wp_remote_get(
            self::CALENDAR_API_URL . '/users/me/calendarList',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token
                )
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['items'])) {
            return $body['items'];
        }

        return array();
    }

    /**
     * Check if connected
     */
    public static function is_connected() {
        $access_token = get_option('fluent_booking_google_access_token');
        return !empty($access_token);
    }

    /**
     * Disconnect
     */
    public static function disconnect() {
        delete_option('fluent_booking_google_access_token');
        delete_option('fluent_booking_google_refresh_token');
        delete_option('fluent_booking_google_token_expires');
    }
}
