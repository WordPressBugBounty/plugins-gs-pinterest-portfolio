<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

class Builder {

    private $option_name = 'gspin_shortcode_prefs';

    /**
     * Constructor of the class.
     * 
     * @since 2.0.12
     */
    public function __construct() {

        add_action( 'wp_ajax_gspin_update_shortcode', array( $this, 'update_shortcode' ) );
        add_action( 'wp_ajax_gspin_create_shortcode', array( $this, 'create_shortcode' ) );
        add_action( 'wp_ajax_gspin_clone_shortcode', array( $this, 'clone_shortcode' ) );
        add_action( 'wp_ajax_gspin_get_shortcode', array( $this, 'get_shortcode' ) );
        add_action( 'wp_ajax_gspin_get_shortcodes', array( $this, 'get_shortcodes' ) );
        add_action( 'wp_ajax_gspin_delete_shortcodes', array( $this, 'delete_shortcode' ) );
        add_action( 'wp_ajax_gspin_temp_save_shortcode_settings', array( $this, 'save_temp_shortcode_settings' ) );

        add_action( 'wp_ajax_gspin_get_shortcode_pref', array( $this, 'get_preferences' ) );
        add_action( 'wp_ajax_gspin_save_shortcode_pref', array( $this, 'save_preferences' ) );
        add_action( 'wp_ajax_gspin_sync_data', array( $this, 'sync_pin_data' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_action( 'template_include', array( $this, 'display' ) );
        add_action( 'show_admin_bar', array( $this, 'hide_adminbar' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

    }

    /**
     * Enqueue admin scripts.
     * 
     * @since  2.0.12
     * @return void
     */
    public function admin_scripts($hook) {

        if ('toplevel_page_gsp-pinterest-main' != $hook) {
            return;
        }

        wp_register_style(
            'gs-zmdi-fonts',
            GSPIN_PLUGIN_URI . '/assets/libs/material-design-iconic-font/css/material-design-iconic-font.min.css',
            '',
            GSPIN_VERSION,
            'all'
        );

        wp_enqueue_style(
            'gs-pinterest-builder-shortcode',
            GSPIN_PLUGIN_URI . '/assets/admin/css/shortcode.min.css',
            array('gs-zmdi-fonts'),
            GSPIN_VERSION,
            'all'
        );

        $data = array(
            'nonce' => array(
                'create_shortcode'                 => wp_create_nonce('_gspin_create_shortcode_gs_'),
                'clone_shortcode'                 => wp_create_nonce('_gspin_clone_shortcode_gs_'),
                'update_shortcode'                 => wp_create_nonce('_gspin_update_shortcode_gs_'),
                'delete_shortcodes'             => wp_create_nonce('_gspin_delete_shortcodes_gs_'),
                'temp_save_shortcode_settings'     => wp_create_nonce('_gspin_temp_save_shortcode_settings_gs_'),
                'save_shortcode_pref'             => wp_create_nonce('_gspin_save_shortcode_pref_gs_'),
                'sync_data'                     => wp_create_nonce('_gspin_sync_data_gs_'),
            ),
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'adminurl' => admin_url(),
            'siteurl'  => home_url()
        );
        $data['shortcode_settings'] = $this->get_shortcode_default_settings();
        $data['shortcode_options']  = $this->get_shortcode_options();
        $data['translations']       = $this->get_strings();
        $data['preference']         = $this->get_default_prefs();
        $data['preference_options'] = $this->get_preference_options();

        wp_enqueue_script(
            'gs-pinterest-shortcode',
            GSPIN_PLUGIN_URI . '/assets/admin/js/shortcode.min.js',
            array('jquery'),
            GSPIN_VERSION,
            true
        );

        wp_localize_script('gs-pinterest-shortcode', 'GS_PINTEREST_DATA', $data);
    }

    /**
     * Returns $wpdb global variable.
     * 
     * @since  1.10.14
     */
    public function get_wpdb() {
        global $wpdb;

        if (wp_doing_ajax()) {
            $wpdb->show_errors = false;
        }

        return $wpdb;
    }

    /**
     * Get defined database columns.
     * 
     * @since  1.10.14
     * @return array Shortcode table database columns.
     */
    public function get_db_columns() {
        return array(
            'shortcode_name'     => '%s',
            'shortcode_settings' => '%s',
            'created_at'         => '%s',
            'updated_at'         => '%s',
        );
    }

    /**
     * Checks for database errors.
     * 
     * @since  1.10.14
     * @return bool true/false based on the error status.
     */
    public function has_db_error() {
        $wpdb = $this->get_wpdb();

        if ('' === $wpdb->last_error) {
            return false;
        }

        return true;
    }

    /**
     * Returns the shortcode default settings.
     * 
     * @since  2.0.12
     * @return array The predefined default settings for each shortcode.
     */
    public function get_shortcode_default_settings() {
        return array(
            'userid'                  => '',
            'board_name'              => '',
            'count'                   => 20,
            'show_pin_title'          => false,
            'theme'                   => 'gs_pin_theme1',
            'link_target'             => '_blank',
            'columns'                 => '4',
            'columns_mobile'          => '12',
            'columns_mobile_portrait' => '12',
            'columns_tablet'          => '6',
            'gutter'                  => '10',
            'board_width'             => '400',
            'pin_width'               => '80',
            'pin_height'              => '320'
        );
    }

    /**
     * Returns the shortcode by given id.
     * 
     * @since  2.0.12
     * 
     * @param mixed $shortcode_id The shortcode id.
     * @param bool  $is_ajax       Ajax status.
     * 
     * @return array|JSON The shortcode.
     */
    public function _get_shortcode( $shortcode_id, $is_ajax = false ) {
        if ( empty( $shortcode_id ) ) {
            if ( $is_ajax ) wp_send_json_error( __( 'Shortcode ID missing', 'gs-pinterest' ), 400 );
            return false;
        }

        $shortcode = wp_cache_get( 'gspin_shortcode' . $shortcode_id, 'gs_pinterest' );

        // Return the cache if found
        if ( $shortcode !== false ) {
            if ( $is_ajax ) wp_send_json_success( $shortcode );
            return $shortcode;
        }

        $wpdb      = $this->get_wpdb();
        $tableName = plugin()->db->get_shortcodes_table();
        $shortcode = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tableName} WHERE id = %d LIMIT 1", absint($shortcode_id) ), ARRAY_A );

        if ( $shortcode ) {

            $shortcode["shortcode_settings"] = json_decode( $shortcode["shortcode_settings"], true );
            $shortcode["shortcode_settings"] = $this->validate_shortcode_settings( $shortcode["shortcode_settings"] );

            wp_cache_add( 'gspin_shortcode' . $shortcode_id, $shortcode, 'gs_pinterest' );

            if ( $is_ajax ) {
                wp_send_json_success( $shortcode );
            }

            return $shortcode;
        }

        if ( $is_ajax ) {
            wp_send_json_error( __('No shortcode found', 'gs-pinterest'), 404 );
        }

        return false;
    }

    /**
     * Ajax endpoint for get shortcodes.
     * 
     * @since  2.0.12
     * @return JSON The response as json object.
     */
    public function get_shortcodes() {
        return $this->fetch_shortcodes( null, wp_doing_ajax() );
    }

    /**
     * Ajax endpoint for get shortcodes.
     * 
     * @since  2.0.12
     * @return JSON The response as json object.
     */
    public function get_shortcodes_as_list() {
        return $this->fetch_shortcodes( null, false );
    }

    /**
     * Fetch shortcodes by given shortcode ids.
     * 
     * @since  2.0.12
     * 
     * @param mixed $shortcode_ids Shortcode ids.
     * @param bool  $is_ajax       Ajax status.
     * @param bool  $minimal        Shortcode minimal result.
     * 
     * @return array|json Fetched shortcodes.
     */
    public function fetch_shortcodes( $shortcode_ids = [], $is_ajax = false, $minimal = false ) {
        $wpdb      = $this->get_wpdb();
        $fields    = $minimal ? 'id, shortcode_name' : '*';
        $tableName = plugin()->db->get_shortcodes_table();

        if ( empty( $shortcode_ids ) ) {
            $shortcodes = wp_cache_get( 'gspin_shortcodes', 'gs_pinterest' );

            if( $shortcodes == false ) {
                $shortcodes = $wpdb->get_results( "SELECT {$fields} FROM {$tableName} ORDER BY id DESC", ARRAY_A );
                wp_cache_add('gspin_shortcodes', $shortcodes, 'gs_pinterest');
            }
            
        } 
        else {
            $how_many     = count( $shortcode_ids );
            $placeholders = array_fill( 0, $how_many, '%d' );
            $format       = implode( ', ', $placeholders );
            $query        = "SELECT {$fields} FROM {$tableName} WHERE id IN($format)";
            $shortcodes   = $wpdb->get_results( $wpdb->prepare( $query, $shortcode_ids ), ARRAY_A );
            
        }

        // check for database error
        if ( $this->has_db_error() ) {
            wp_send_json_error( sprintf( __( 'Database Error: %s' ), $wpdb->last_error ) );
        }

        if ( $is_ajax ) {
            wp_send_json_success( $shortcodes );
        }

        return $shortcodes;
    }

    /**
     * Save shortcode temporary default settings.
     * 
     * @since  2.0.12
     * 
     * @return saved status.
     */
    public function save_temp_shortcode_settings() {
        if ( ! check_admin_referer( '_gspin_temp_save_shortcode_settings_gs_' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __('Unauthorised Request', 'gs-pinterest'), 401 );
        }

        $temp_key = isset( $_POST[ 'temp_key' ] ) ? $_POST[ 'temp_key' ] : null;
        $shortcode_settings = isset( $_POST[ 'shortcode_settings' ] ) ? $_POST[ 'shortcode_settings' ] : null;
        
        if ( empty( $temp_key ) ) wp_send_json_error( __('No temp key provided', 'gs-pinterest'), 400 );
        if ( empty( $shortcode_settings ) ) wp_send_json_error( __('No temp settings provided', 'gs-pinterest'), 400 );

        $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );
        set_transient( $temp_key, $shortcode_settings, DAY_IN_SECONDS ); // save the transient for 1 day

        wp_send_json_success([
            'message' => __('Temp data saved', 'gs-pinterest'),
        ]);
    }

    /**
     * Validate given shortcode settings.
     * 
     * @since  2.0.12
     * 
     * @param  array $settings
     * @return array Shortcode settings.
     */
    public function validate_shortcode_settings( $shortcode_settings ) {
        $shortcode_settings = shortcode_atts( $this->get_shortcode_default_settings(), $shortcode_settings );
        return array_map( 'sanitize_text_field', $shortcode_settings );
    }

    /**
     * Ajax endpoint for deleting shortcode.
     * 
     * @since  2.0.12
     * @return JSON The response as json object.
     */
    public function delete_shortcode() {
        if ( ! check_admin_referer( '_gspin_delete_shortcodes_gs_' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorised Request', 'gs-pinterest' ), 401 );
        }

        $ids = isset( $_POST['ids'] ) ? $_POST['ids'] : null;

        if ( empty( $ids ) ) {
            wp_send_json_error( __( 'No shortcode ids provided', 'gs-pinterest' ), 400 );
        }

        $wpdb  = $this->get_wpdb();
        $count = count( $ids );

        $ids = implode( ',', array_map( 'absint', $ids ) );
        $tableName = plugin()->db->get_shortcodes_table();
        $wpdb->query( "DELETE FROM {$tableName} WHERE ID IN($ids)" );

        if ( $this->has_db_error() ) {
            wp_send_json_error( sprintf( __( 'Database Error: %s' ), $wpdb->last_error ), 500 );
        }

        $shortcode_id = ! empty( $_POST[ 'id' ] ) ? absint( $_POST[ 'id' ] ) : null;
        $shortcode    = $this->_get_shortcode( $shortcode_id, false );

        clear_schedule_event( $shortcode, $_POST );

        // Delete the shortcode cache
        wp_cache_delete( 'gspin_shortcodes', 'gs_pinterest' );

        do_action( 'gs_pin_shortcode_deleted' );
        do_action( 'gsp_shortcode_deleted' );

        $m = _n( "Shortcode has been deleted", "Shortcodes have been deleted", $count, 'gs-pinterest' ) ;
        wp_send_json_success( [ 'message' => $m ] );
    }

    /**
     * Ajax endpoint for update shortcode.
     * 
     * @since  2.0.12
     * @return int The numbers of row affected.
     */
    public function update_shortcode( $shortcode_id = null, $nonce = null ) {

        if ( ! $shortcode_id ) {
            $shortcode_id = !empty( $_POST['id']) ? $_POST['id'] : null;
        }
            
        if ( ! $nonce ) {
            $nonce = $_POST['_wpnonce'] ?: null;
        }

        $this->_update_shortcode( $shortcode_id, $nonce, $_POST, true );

    }

    /**
     * Ajax endpoint for update shortcode.
     * 
     * @since  2.0.12
     * @return int The numbers of row affected.
     */
    public function _update_shortcode( $shortcode_id, $nonce, $fields, $is_ajax ) {

        if ( ! wp_verify_nonce( $nonce, '_gspin_update_shortcode_gs_') || ! current_user_can( 'manage_options' ) ) {
            if ( $is_ajax ) wp_send_json_error( __('Unauthorised Request', 'gs-pinterest'), 401 );
            return false;
        }

        if ( empty($shortcode_id) ) {
            if ( $is_ajax ) wp_send_json_error( __('Shortcode ID missing', 'gs-pinterest'), 400 );
            return false;
        }

        $_shortcode = $this->_get_shortcode( $shortcode_id, false );

        $shortcode_id = ! empty( $_POST[ 'id' ] ) ? absint( $_POST[ 'id' ] ) : null;

        if ( empty( $shortcode_id ) ) {
            wp_send_json_error( __('Shortcode ID missing', 'gs-pinterest'), 400 );
        }

        $_shortcode = $this->_get_shortcode( $shortcode_id, false );
        
        if ( empty($_shortcode) ) {
            if ( $is_ajax ) wp_send_json_error( __('No shortcode found to update', 'gs-pinterest'), 404 );
            return false;
        }

        $shortcode_name = !empty( $fields['shortcode_name'] ) ? sanitize_text_field( $fields['shortcode_name'] ) : sanitize_text_field( $_shortcode['shortcode_name'] );
        $shortcode_settings = !empty( $fields['shortcode_settings']) ? $fields['shortcode_settings'] : $_shortcode['shortcode_settings'];
    
        $shortcode_settings  = $this->validate_shortcode_settings( $shortcode_settings );
        $tableName           = plugin()->db->get_shortcodes_table();
        $wpdb                = $this->get_wpdb();
    
        $data = array(
            "shortcode_name"     => $shortcode_name,
            "shortcode_settings" => json_encode( $shortcode_settings ),
            "updated_at" 		    => current_time( 'mysql')
        );

        $update_id = $wpdb->update( $tableName, $data, array( 'id' => absint( $shortcode_id ) ), $this->get_db_columns() );

        if ( $this->has_db_error() ) {
            if ( $is_ajax ) wp_send_json_error( sprintf( __( 'Database Error: %1$s', 'gs-pinterest'), $wpdb->last_error), 500 );
            return false;
        }

        clear_schedule_event( $_shortcode, $_POST );

        // Delete the shortcode cache
        wp_cache_delete( 'gspin_shortcodes', 'gs_pinterest' );
        wp_cache_delete( 'gspin_shortcode' . $shortcode_id, 'gs_pinterest' );

        do_action( 'gs_pin_shortcode_updated', $update_id );
        do_action( 'gsp_shortcode_updated', $update_id );
        
        if ($is_ajax) wp_send_json_success( array(
            'message' => __('Shortcode updated', 'gs-pinterest'),
            'shortcode_id' => $update_id
        ));
    
        return $update_id;
    }

    /**
     * Ajax endpoint for create shortcode.
     * 
     * @since  2.0.12
     * @return json WP json response.
     */
    public function create_shortcode() {
        if ( ! check_admin_referer( '_gspin_create_shortcode_gs_' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorised Request', 'gs-pinterest' ), 401 );
        }

        $shortcode_name     = ! empty( $_POST[ 'shortcode_name' ] ) ? sanitize_text_field( $_POST[ 'shortcode_name' ] ) : __( 'Untitled', 'gs-pinterest' );
        $shortcode_settings = ! empty( $_POST[ 'shortcode_settings' ] ) ? $_POST[ 'shortcode_settings' ] : '';

        if ( empty( $shortcode_settings ) || ! is_array( $shortcode_settings ) ) {
            wp_send_json_error( __( 'Please configure the settings properly', 'gs-pinterest' ), 206 );
        }

        $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );

        $wpdb               = $this->get_wpdb();
        $tableName          = plugin()->db->get_shortcodes_table();

        $data = array(
            "shortcode_name"     => $shortcode_name,
            "shortcode_settings" => json_encode( $shortcode_settings )
        );

        $wpdb->insert( $tableName, $data, $this->get_db_columns() );

        // check for database error
        if ( $this->has_db_error() ) {
            wp_send_json_error( sprintf( __( 'Database Error: %s' ), $wpdb->last_error ), 500 );
        }

        // Delete the shortcode cache
        wp_cache_delete( 'gspin_shortcodes', 'gs_pinterest' );

        do_action( 'gs_pin_shortcode_created', $wpdb->insert_id );
        do_action( 'gsp_shortcode_created', $wpdb->insert_id );

        // send success response with inserted id
        wp_send_json_success( array(
            'message'      => __( 'Shortcode created successfully', 'gs-pinterest' ),
            'shortcode_id' => $wpdb->insert_id
        ));
    }

    /**
     * Ajax endpoint for clone a shortcode.
     * 
     * @since  2.0.12
     * @return json WP json response.
     */
    public function clone_shortcode() {
        if ( ! check_admin_referer( '_gspin_clone_shortcode_gs_' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorised Request', 'gs-pinterest' ), 401 );
        }

        $clone_id  = ! empty( $_POST[ 'clone_id' ] ) ? absint( $_POST[ 'clone_id' ] ) : '';

        if ( empty( $clone_id ) ) {
            wp_send_json_error( __( 'Clone Id not provided', 'gs-pinterest' ), 400 );
        }

        $clone_shortcode = $this->_get_shortcode( $clone_id, false );

        if ( empty( $clone_shortcode ) ) {
            wp_send_json_error( __( 'No shortcode found to clone.', 'gs-pinterest' ), 404 );
        }

        $shortcode_settings = $clone_shortcode[ 'shortcode_settings' ];
        $shortcode_name     = $clone_shortcode[ 'shortcode_name' ] .' '. __( '- Cloned', 'gs-pinterest' );
        $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );

        $wpdb      = $this->get_wpdb();
        $tableName = plugin()->db->get_shortcodes_table();

        $data = array(
            "shortcode_name"     => $shortcode_name,
            "shortcode_settings" => json_encode($shortcode_settings)
        );

        $wpdb->insert(
            $tableName,
            $data,
            $this->get_db_columns()
        );

        if ( $this->has_db_error() ) {
            wp_send_json_error( sprintf( __( 'Database Error: %s' ), $wpdb->last_error ), 500 );
        }

        // Delete the shortcode cache
        wp_cache_delete( 'gspin_shortcodes', 'gs_pinterest' );

        // Get the cloned shortcode
        $shotcode = $this->_get_shortcode( $wpdb->insert_id, false );

        // send success response with inserted id
        wp_send_json_success( array(
            'message'   => __( 'Shortcode cloned successfully', 'gs-pinterest' ),
            'shortcode' => $shotcode,
        ));
    }

    /**
     * Ajax endpoint for get a shortcode.
     * 
     * @since  1.10.14
     * @return void
     */
    public function get_shortcode() {
        $shortcode_id = ! empty( $_GET['id']) ? absint( $_GET['id'] ) : null;
        return $this->_get_shortcode( $shortcode_id, wp_doing_ajax() );
    }

    /**
     * Returns themes options.
     * 
     * @since  2.0.12
     * @return array Themes options.
     */
    public function get_themes() {
        $freeThemes = array(
            [
                'label' => __( 'Theme (Pins)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme1'
            ],
            [
                'label' => __( 'Theme (Board)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme_two'
            ]
        );

        $proThemes = array(
            [
                'label' => __( 'Theme 2 (Pin Links - PRO)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme2' 
            ],
            [
                'label' => __( 'Theme 3 (Hover - PRO)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme3'
            ],
            [
                'label' => __( 'Theme 4 (Popup - PRO)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme4'
            ],
            [
                'label' => __( 'Theme 5 (Grayscale - PRO)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme5'
            ],
            [
                'label' => __( 'User Profile (PRO)', 'gs-pinterest' ),
                'value' => 'gs_pin_theme6'
            ]
        );

        if ( ! plugin()->helpers->is_pro_active() ) {
            $proThemes = array_map( function( $item ) {
                $item['pro'] = true;
                return $item;
            }, $proThemes );
        }

        return array_merge( $freeThemes, $proThemes );
    }

    /**
     * Returns link types options.
     * 
     * @since  2.0.12
     * @return array Link type options.
     */
    public function get_link_types() {
        return array(
            array(
                'value' => '_blank',
                'label' => __( 'New Tab', 'gs-pinterest' )
            ),
            array(
                'value' => '_self',
                'label' => __( 'Same Window', 'gs-pinterest' )
            )
        );
    }

    /**
     * Retrives WP registered possible thumbnail sizes.
     * 
     * @since  1.10.14
     * @return array   image sizes.
     */
    public function get_possible_thumbnail_sizes() {
        $sizes = get_intermediate_image_sizes();

        if ( empty($sizes) ) {
            return [];
        }

        $result = [];
        foreach ( $sizes as $size ) {
            $result[] = [
                'label' => ucwords( preg_replace('/_|-/', ' ', $size) ),
                'value' => $size
            ];
        }
        
        return $result;
    }

    /**
     * Returns predefined columns
     * 
     * @since  2.0.12
     * @return array Predefined columns.
     */
    public function columns() {
        return array(
            array(
                'label' => __( '1 Column', 'gs-pinterest' ),
                'value' => '12'
            ),
            array(
                'label' => __( '2 Columns', 'gs-pinterest' ),
                'value' => '6'
            ),
            array(
                'label' => __( '3 Columns', 'gs-pinterest' ),
                'value' => '4'
            ),
            array(
                'label' => __( '4 Columns', 'gs-pinterest' ),
                'value' => '3'
            ),
            array(
                'label' => __( '5 Columns', 'gs-pinterest' ),
                'value' => '2_4'
            ),
            array(
                'label' => __( '6 Columns', 'gs-pinterest' ),
                'value' => '2'
            )
        );
    }

    /**
     * Returns default options.
     * 
     * @since  2.0.12
     * @return array Default options.
     */
    public function get_shortcode_options() {

        return [
            'themes'                      => $this->get_themes(),
            'link_targets'                => $this->get_link_types(),
            'gs_member_thumbnail_sizes'   => $this->get_possible_thumbnail_sizes(),
            
            // responsive
            'columns'                 => $this->columns(),
            'columns_tablet'          => $this->columns(),
            'columns_mobile_portrait' => $this->columns(),
            'columns_mobile'          => $this->columns(),
            'order' => array(
                array(
                    'label' => __( 'DESC', 'gs-pinterest' ),
                    'value' => 'DESC'
                ),
                array(
                    'label' => __( 'ASC', 'gs-pinterest' ),
                    'value' => 'ASC'
                )
            )
        ];
    }
    
    /**
     * Ajax endpoint for retriving shortcode preferences.
     * 
     * @since 2.0.12
     */
    public function _get_preferences( $is_ajax = false ) {

        $pref = get_option( $this->option_name );

        if ( empty( $pref ) ) {
            $pref = $this->get_default_prefs();
            $this->save( $pref, false );
        }

        if ( $is_ajax ) {
            wp_send_json_success( $pref );
        }

        return $pref;
    }

    /**
     * Ajax endpoint for retriving shortcode preferences.
     * 
     * @since 2.0.12
     */
    public function get_preferences() {
        return $this->_get_preferences( wp_doing_ajax() );
    }

    /**
     * Ajax endpoint for saving shortcode preferences.
     * 
     * @since 2.0.12
     */
    public function save_preferences() {

        check_ajax_referer( '_gspin_save_shortcode_pref_gs_', '_wpnonce' );

        if ( empty($_POST['prefs']) ) {
            wp_send_json_error( __( 'No preference provided', 'gs-pinterest' ), 400 );
        }

        $this->save( $_POST['prefs'], true );
    }

    public function sync_pin_data() {

        check_ajax_referer( '_gspin_sync_data_gs_', '_wpnonce' );

        $status = plugin()->pinterest->sync_all_shortcode_pins();

        if ( $status ) wp_send_json_success('Synced successfully');

        wp_send_json_error();
        
    }

    /**
     * Get shortcode preferences options.
     * 
     * @since  2.0.12
     * @return array
     */
    public function get_preference_options() {
        
        return [
            'sync_interval' => $this->sync_interval()
        ];
    }

    public function sync_interval() {
        return [
            [
                'label' => __( '1 Day', 'gs-pinterest' ),
                'value' => 'daily'
            ],
            [
                'label' => __( '7 Days', 'gs-pinterest' ),
                'value' => 'weekly'
            ],
            [
                'label' => __( '15 Days', 'gs-pinterest' ),
                'value' => 'fifteen_days'
            ],
            [
                'label' => __( 'Never', 'gs-pinterest' ),
                'value' => 'never_sync'
            ]
        ];
    }

    /**
     * Get default shortcode preferences.
     * 
     * @since  2.0.12
     * @return array
     */
    public function get_default_prefs() {
        return array(
            'sync_interval'     => 'weekly',
            'gspin_custom_css'  => '',
            'hide_repin_button' => false,
            'disable_lazy_load' => true,
            'lazy_load_class'   => 'skip-lazy'
        );
    }

    /**
     * Helper method for saving shortcode preferences.
     * 
     * @since  2.0.12
     * @return wp_json response.
     */
    public function save( $settings, $is_ajax ) {
        
        $settings = $this->validate( $settings );

        $prev_settings = $settings;
        $current_settings = get_option( 'gspin_shortcode_prefs', [] );
        
        $status = update_option( 'gspin_shortcode_prefs', $settings, 'yes' );

        if( $status ) {

            if( empty($prev_settings['sync_interval']) || empty($current_settings['sync_interval']) || $prev_settings['sync_interval'] !== $current_settings['sync_interval'] ) {

                $shortcodes = $this->fetch_shortcodes();

                foreach( $shortcodes as $shortcode ) {

                    clear_schedule_event( $shortcode );
                }

            }
        }

        do_action( 'gspin_preference_update' );
        do_action('gsp_preference_update');

        if ( $is_ajax ) wp_send_json_success( __( 'Preference saved', 'gs-pinterest' ) );
    }

    public function validate( Array $settings ) {

        foreach ( $settings as $setting_key => $setting_val ) {
            switch ( $setting_key ) {
                case 'gspin_custom_css' :
                    $settings[ $setting_key ] = wp_strip_all_tags( $setting_val );
                    break;
                case 'hide_repin_button' :
                    $settings[ $setting_key ] = wp_validate_boolean( $setting_val );
                    break;
                case 'disable_lazy_load' :
                    $settings[ $setting_key ] = wp_validate_boolean( $setting_val );
                    break;
                default:
                $settings[ $setting_key ] = sanitize_text_field( $setting_val );
            }
        }
        
        return $settings;
    }

    /**
     * Get shortcode preferences.
     * 
     * @since 2.0.12
     * 
     * @param  bool         $is_ajax If want the reponse as ajax response.
     * @return wp_json|array 
     */
    public function get( $key, $default = '' ) {
        $pref = $this->_get_preferences( false );
        return $pref[ $key ] ?? $default;
    }

    /**
     * Plugin dashboard strings.
     * 
     * @since  2.0.12
     * @return array Array of the plugin dashboard strings.
     */
    public function get_strings() {
        return [
            'user-id'                      => __( 'Username', 'gs-pinterest' ),
            'user-id--help'                => __( 'Enter Pinterest username', 'gs-pinterest' ),
            'board-name'                   => __( 'Board Name', 'gs-pinterest' ),
            'board-name--help'             => __( 'Enter Pinterest Board name for Specific board pins', 'gs-pinterest' ),
            'count'                        => __( 'Total Pins to display', 'gs-pinterest' ),
            'count--help'                  => __( 'Set number of pins to display. Default 10, max 25', 'gs-pinterest' ),
            'theme'                        => __( 'Theme', 'gs-pinterest' ),
            'theme--help'                  => __( 'Select preffered styled theme', 'gs-pinterest' ),
            'link-target'                  => __( 'Pins Link Target', 'gs-pinterest' ),
            'link-target--help'            => __( 'Specify target to load the Links, Default New Tab', 'gs-pinterest' ),
            'pin-title'                    => __( 'Pin Title', 'gs-pinterest' ),
            'pin-title--help'              => __( 'Show or Hide Pin Title', 'gs-pinterest' ),
            'gutter'                       => __( 'Gutter', 'gs-pinterest' ),
            'gutter--help'                 => __( 'Set Gutter space in between Pins.', 'gs-pinterest' ),
            'board_width'                  => __( 'Board Width (px)', 'gs-pinterest' ),
            'pin_height'                   => __( 'Scale Height (px)', 'gs-pinterest' ),
            'pin_width'                    => __( 'Scale Width (px)', 'gs-pinterest' ),

            'custom-css'                    => __('Custom CSS', 'gs-pinterest'),
            'preference'                    => __('Preference', 'gs-pinterest'),
            'sync_interval'                 => __( 'Sync Interval', 'gs-pinterest' ),
            'sync_interval--help'           => __( 'Select Sync Interval', 'gs-pinterest' ),
            // responsive labels 
            'columns'                       => __('Desktop Columns', 'gs-pinterest'),
            'columns--help'                 => __('Enter the columns number for desktop', 'gs-pinterest'),
            'columns-tablet'                => __('Tablet Columns', 'gs-pinterest'),
            'columns-tablet--help'          => __('Enter the columns number for tablet', 'gs-pinterest'),
            'columns-mobile-portrait'       => __('Portrait Mobile Columns', 'gs-pinterest'),
            'columns-mobile-portrait--help' => __('Enter the columns number for portrait or large display mobile', 'gs-pinterest'),
            'columns-mobile'                => __('Mobile Columns', 'gs-pinterest'),
            'columns-mobile--help'          => __('Enter the columns number for mobile', 'gs-pinterest'),

            'hide-repin-button'             => __('Hide Repin Button', 'gs-pinterest'),
            'hide-repin-button--help'       => __('Hide Repin Button for Pins', 'gs-pinterest'),
            'disable-lazy-load'             => __('Disable Lazy Load', 'gs-pinterest'),
            'disable-lazy-load--help'       => __('Disable Lazy Load for Pins', 'gs-pinterest'),
            'lazy-load-class'               => __('Lazy Load Class', 'gs-pinterest'),
            'lazy-load-class--help'         => __('Add class to disable Lazy Loading, multiple classes should be separated by space', 'gs-pinterest'),
            'save-preference'               => __('Save Preference', 'gs-pinterest'),
            'sync-data'                     => __( 'Sync Pinterest Data', 'gs-pinterest' ),
            'sync-data-button'              => __( 'SYNC DATA NOW', 'gs-pinterest' ),
            'shortcodes'                    => __('Shortcodes', 'gs-pinterest'),
            'shortcode'                     => __('Shortcode', 'gs-pinterest'),
            'global-settings-label'         => __('Global settings which are going to work on the whole plugin.', 'gs-pinterest'),
            'all-shortcodes'                => __('All shortcodes', 'gs-pinterest'),
            'create-shortcode'              => __('Create Shortcode', 'gs-pinterest'),
            'create-new-shortcode'          => __('Create New Shortcode', 'gs-pinterest'),
            'name'                          => __('Name', 'gs-pinterest'),
            'action'                        => __('Action', 'gs-pinterest'),
            'actions'                       => __('Actions', 'gs-pinterest'),
            'edit'                          => __('Edit', 'gs-pinterest'),
            'clone'                         => __('Clone', 'gs-pinterest'),
            'delete'                        => __('Delete', 'gs-pinterest'),
            'delete-all'                    => __('Delete All', 'gs-pinterest'),
            'create-a-new-shortcode-and'    => __('Create a new shortcode & save it to use globally in anywhere', 'gs-pinterest'),
            'edit-shortcode'                => __('Edit Shortcode', 'gs-pinterest'),
            'general-settings'              => __('General Settings', 'gs-pinterest'),
            'style-settings'                => __('Style Settings', 'gs-pinterest'),
            'query-settings'                => __('Query Settings', 'gs-pinterest'),
            'name-of-the-shortcode'         => __('Name of the Shortcode', 'gs-pinterest'),
            'save-shortcode'                => __('Save Shortcode', 'gs-pinterest'),
            'preview-shortcode'             => __('Preview Shortcode', 'gs-pinterest')
        ];
    }

    /**
     * Enqueue scripts for the preview only.
     * 
     * @since  2.0.12
     * @return void
     */
    public function scripts( $hook ) {
        if ( ! plugin()->helpers->is_preview() ) {
            return;
        }

        wp_enqueue_style(
            'gs-pinterest-shortcode-preview',
            GSPIN_PLUGIN_URI . '/assets/admin/css/shortcode-preview.min.css',
            '', GSPIN_VERSION, 'all'
        );
    }

    /**
     * Displays the shortcode preview.
     * 
     * @since  2.0.12
     * @return void
     */
    public function display( $template ) {
        global $wp, $wp_query;
        
        if ( plugin()->helpers->is_preview() ) {
            // Create our fake post
            $post_id              = rand( 1, 99999 ) - 9999999;
            $post                 = new \stdClass();
            $post->ID             = $post_id;
            $post->post_author    = 1;
            $post->post_date      = current_time( 'mysql' );
            $post->post_date_gmt  = current_time( 'mysql', 1 );
            $post->post_title     = __( 'Shortcode Preview', 'gs-pinterest' );
            $post->post_content   = '[gs_pinterest preview="yes" id="' . esc_attr( sanitize_key( $_REQUEST['gspin_shortcode_preview'] ) ) . '"]';
            $post->post_status    = 'publish';
            $post->comment_status = 'closed';
            $post->ping_status    = 'closed';
            $post->post_name      = 'fake-page-' . rand( 1, 99999 ); // append random number to avoid clash
            $post->post_type      = 'page';
            $post->filter         = 'raw'; // important!

            // Convert to WP_Post object
            $wp_post = new \WP_Post( $post );

            // Add the fake post to the cache
            wp_cache_add( $post_id, $wp_post, 'posts' );

            // Update the main query
            $wp_query->post                 = $wp_post;
            $wp_query->posts                = array( $wp_post );
            $wp_query->queried_object       = $wp_post;
            $wp_query->queried_object_id    = $post_id;
            $wp_query->found_posts          = 1;
            $wp_query->post_count           = 1;
            $wp_query->max_num_pages        = 1; 
            $wp_query->is_page              = true;
            $wp_query->is_singular          = true; 
            $wp_query->is_single            = false; 
            $wp_query->is_attachment        = false;
            $wp_query->is_archive           = false; 
            $wp_query->is_category          = false;
            $wp_query->is_tag               = false; 
            $wp_query->is_tax               = false;
            $wp_query->is_author            = false;
            $wp_query->is_date              = false;
            $wp_query->is_year              = false;
            $wp_query->is_month             = false;
            $wp_query->is_day               = false;
            $wp_query->is_time              = false;
            $wp_query->is_search            = false;
            $wp_query->is_feed              = false;
            $wp_query->is_comment_feed      = false;
            $wp_query->is_trackback         = false;
            $wp_query->is_home              = false;
            $wp_query->is_embed             = false;
            $wp_query->is_404               = false; 
            $wp_query->is_paged             = false;
            $wp_query->is_admin             = false;
            $wp_query->is_preview           = false; 
            $wp_query->is_robots            = false; 
            $wp_query->is_posts_page        = false;
            $wp_query->is_post_type_archive = false;

            // Update globals
            $GLOBALS['wp_query'] = $wp_query;
            $wp->register_globals();

            include GSPIN_PLUGIN_DIR . 'includes/shortcode-builder/preview.php';

            return;
        }

        return $template;
    }

    /**
     * Hide admin bar from the preview window.
     * 
     * @since  2.0.12
     * @return bool
     */
    public function hide_adminbar( $visibility ) {
        if ( plugin()->helpers->is_preview() ) {
            return false;
        }

        return $visibility;
    }
}
