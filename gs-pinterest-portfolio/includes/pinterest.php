<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

class Pinterest {

    public function __construct() {

        add_action('gs_pinterest_pin_sync_event', [$this, 'sync_pins'], 10, 2);
        add_filter('cron_schedules', [$this, 'add_custom_interval']);
    }

    public function add_custom_interval($schedules) {
        $schedules['fifteen_days'] = [
            'interval' => 1296000,
            'display'  => __('Fifteen Days', 'gs-pinterest')
        ];
        return $schedules;
    }

    public function sync_pins( $username, $board = '' ) {

        $response = $this->get_pinterest_response($username, $board);

        if (is_array($response) && !is_wp_error($response)) {

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($body['data'])) {
                return update_option($this->get_option_key($username, $board), $body['data']);
            }
        }

        return false;
    }

    public function sync_all_shortcode_pins() {
        
        $shortcodes = plugin()->builder->fetch_shortcodes();

        foreach ( $shortcodes as $shortcode ) {
            if(empty($shortcode['userid'])) continue;
            $this->sync_pins( $shortcode['userid'], $shortcode['board_name'] );
        }

        return true;

    }

    public function get_pinterest_response($username, $board = '') {
        if (empty($board)) {
            $url = sprintf('https://api.pinterest.com/v3/pidgets/users/%s/pins/', $username);
        } else {
            $url = sprintf('https://api.pinterest.com/v3/pidgets/boards/%s/%s/pins/', $username, $board);
        }
        return wp_remote_get(esc_url_raw($url));
    }

    public function get_option_key($username, $board = '') {
        return "gspin_{$username}_{$board}_pins";
    }

    public function get_data($username, $board = '') {

        if (empty($username)) return [];

        $option_key     =    $this->get_option_key($username, $board);
        $savedData      =    get_option($option_key);

        gspin_add_schedule_event( $username, $board );
        
        if (false === $savedData) {

            $response = $this->get_pinterest_response($username, $board);

            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['data'])) {
                    $savedData = $body['data'];
                    update_option($option_key, $savedData);
                } else {
                    $savedData = [];
                }
            }
        }

        return $savedData;
    }

    public function get_board_pins_by_user($username, $board, $count, $fields = []) {
        $savedData = $this->get_data($username, $board);
        if (empty($savedData) || empty($savedData['pins'])) return [];
        return plugin()->helpers->pluck($savedData['pins'], $count, $fields);
    }

    public function get_user_profile($username, $count = 50, $fields = []) {
        $savedData = $this->get_data($username);
        if (empty($savedData) || empty($savedData['pins'])) return [];
        $savedData['pins'] = plugin()->helpers->pluck($savedData['pins'], $count, $fields);
        return $savedData;
    }
}
