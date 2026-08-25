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

        $data = $this->fetch_pins_data($username, $board);

        if (!empty($data['pins'])) {
            $data['gspin_fetched_all'] = 2;
            return update_option($this->get_option_key($username, $board), $data, false);
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

    public function get_pinterest_response($username, $board = '', $query_args = []) {
        if (empty($board)) {
            $url = sprintf('https://api.pinterest.com/v3/pidgets/users/%s/pins/', $username);
        } else {
            $url = sprintf('https://api.pinterest.com/v3/pidgets/boards/%s/%s/pins/', $username, $board);
        }

        $query_args = array_filter((array) $query_args, function($value) {
            return $value !== '' && $value !== null;
        });

        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }

        return wp_remote_get(esc_url_raw($url), [
            'timeout' => 15,
        ]);
    }

    protected function parse_pinterest_response($response) {
        if (is_wp_error($response) || !is_array($response)) {
            return [];
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return is_array($body) ? $body : [];
    }

    protected function get_payload_from_response($body) {
        if (empty($body['data']) || !is_array($body['data'])) {
            return [];
        }

        if ($this->is_list_array($body['data'])) {
            return [
                'pins' => $body['data'],
            ];
        }

        return $body['data'];
    }

    protected function get_pins_from_response($body) {
        if (empty($body) || !is_array($body)) {
            return [];
        }

        if (isset($body['data']['pins']) && is_array($body['data']['pins'])) {
            return $body['data']['pins'];
        }

        if (isset($body['data']) && $this->is_list_array($body['data'])) {
            return $body['data'];
        }

        if (isset($body['pins']) && is_array($body['pins'])) {
            return $body['pins'];
        }

        return [];
    }

    protected function get_bookmark_from_response($body) {
        $candidates = [
            isset($body['bookmark']) ? $body['bookmark'] : '',
            isset($body['next_bookmark']) ? $body['next_bookmark'] : '',
            isset($body['data']['bookmark']) ? $body['data']['bookmark'] : '',
            isset($body['page']['cursor']) ? $body['page']['cursor'] : '',
            isset($body['data']['page']['cursor']) ? $body['data']['page']['cursor'] : '',
        ];

        foreach ($candidates as $bookmark) {
            if (!is_string($bookmark)) {
                continue;
            }

            $bookmark = trim($bookmark);
            if ($bookmark === '' || strtolower($bookmark) === 'null') {
                continue;
            }

            return $bookmark;
        }

        return '';
    }

    protected function is_list_array($value) {
        if (!is_array($value)) {
            return false;
        }

        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    public function get_option_key($username, $board = '') {
        return "gspin_{$username}_{$board}_pins";
    }

    protected function append_unique_pins(&$all_pins, &$seen_ids, $pins) {
        $added = 0;

        foreach ((array) $pins as $pin) {
            $pin_id = (is_array($pin) && isset($pin['id'])) ? (string) $pin['id'] : '';

            if ($pin_id !== '' && isset($seen_ids[$pin_id])) {
                continue;
            }

            if ($pin_id !== '') {
                $seen_ids[$pin_id] = true;
            }

            $all_pins[] = $pin;
            $added++;
        }

        return $added;
    }

    protected function parse_board_url($url, $fallback_username) {
        $result = [
            'username' => $fallback_username,
            'slug'     => '',
        ];

        if (!is_string($url) || $url === '') {
            return $result;
        }

        $path = $url;
        if (preg_match('#^https?://#i', $url)) {
            $parsed_path = wp_parse_url($url, PHP_URL_PATH);
            $path = $parsed_path ? $parsed_path : $url;
        }

        $parts = array_values(array_filter(explode('/', $path), 'strlen'));
        if (count($parts) < 2) {
            return $result;
        }

        $slug = $parts[1];

        // Internal/hidden boards such as _ad_only are not available via pidgets.
        if ($slug === '' || strpos($slug, '_') === 0) {
            return $result;
        }

        $result['username'] = $parts[0];
        $result['slug']     = $slug;

        return $result;
    }

    protected function get_board_targets_from_pins($pins, $username) {
        $targets = [];

        foreach ((array) $pins as $pin) {
            if (empty($pin['board']) || !is_array($pin['board']) || empty($pin['board']['url'])) {
                continue;
            }

            $parsed = $this->parse_board_url($pin['board']['url'], $username);
            if ($parsed['slug'] === '') {
                continue;
            }

            $key = strtolower($parsed['username'] . '/' . $parsed['slug']);
            if (!isset($targets[$key])) {
                $targets[$key] = $parsed;
            }
        }

        return array_values($targets);
    }

    protected function collect_pins_from_endpoint($username, $board, &$all_pins, &$seen_ids, &$payload, $max_pins) {
        $bookmark  = '';
        $page      = 1;
        $max_pages = 10;

        while ($page <= $max_pages && count($all_pins) < $max_pins) {
            $query_args = [];
            if ($bookmark !== '') {
                $query_args['bookmark'] = $bookmark;
            }

            $body = $this->parse_pinterest_response(
                $this->get_pinterest_response($username, $board, $query_args)
            );

            if (empty($body)) {
                break;
            }

            if (empty($payload)) {
                $payload = $this->get_payload_from_response($body);
            }

            $pins = $this->get_pins_from_response($body);
            if (empty($pins)) {
                break;
            }

            $added = $this->append_unique_pins($all_pins, $seen_ids, $pins);
            if ($added === 0) {
                break;
            }

            $next_bookmark = $this->get_bookmark_from_response($body);
            if ($next_bookmark !== '' && $next_bookmark !== $bookmark) {
                $bookmark = $next_bookmark;
                $page++;
                continue;
            }

            break;
        }
    }

    public function fetch_pins_data($username, $board = '') {
        $all_pins  = [];
        $seen_ids  = [];
        $payload   = [];
        $max_pins  = 250;
        $max_boards = 12;

        $this->collect_pins_from_endpoint($username, $board, $all_pins, $seen_ids, $payload, $max_pins);

        // User pin feeds are capped at 50. Pull extra unique pins from public boards.
        if (empty($board) && count($all_pins) < $max_pins) {
            $board_targets = $this->get_board_targets_from_pins($all_pins, $username);
            $fetched_boards = 0;

            foreach ($board_targets as $target) {
                if ($fetched_boards >= $max_boards || count($all_pins) >= $max_pins) {
                    break;
                }

                $this->collect_pins_from_endpoint(
                    $target['username'],
                    $target['slug'],
                    $all_pins,
                    $seen_ids,
                    $payload,
                    $max_pins
                );

                $fetched_boards++;
            }
        }

        if (empty($all_pins)) {
            return [];
        }

        if (!is_array($payload) || $this->is_list_array($payload)) {
            $payload = [
                'pins' => $all_pins,
            ];
        } else {
            $payload['pins'] = $all_pins;
        }

        return $payload;
    }

    public function get_data($username, $board = '') {

        if (empty($username)) return [];

        $option_key = $this->get_option_key($username, $board);
        $savedData  = get_option($option_key);

        gspin_add_schedule_event( $username, $board );

        $has_pins    = is_array($savedData) && !empty($savedData['pins']);
        $is_complete = $has_pins && (int) (!empty($savedData['gspin_fetched_all']) ? $savedData['gspin_fetched_all'] : 0) >= 2;

        if (!$is_complete) {
            $fresh_data = $this->fetch_pins_data($username, $board);

            if (!empty($fresh_data['pins'])) {
                $fresh_data['gspin_fetched_all'] = 2;
                update_option($option_key, $fresh_data, false);
                $savedData = $fresh_data;
            } elseif (!$has_pins) {
                $savedData = [];
            }
        }

        if (is_array($savedData)) {
            unset($savedData['gspin_fetched_all']);
            return $savedData;
        }

        return [];
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
