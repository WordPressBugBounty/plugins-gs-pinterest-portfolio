<?php
namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

/**
 * Represents as a database utilites.
 * 
 * @since 2.0.12
 */
class Database {

    /**
     * Returns the plugin database shortcodes table name.
     * 
     * @since  2.0.12
     * @return string Database table name.
     */
    public function get_shortcodes_table() {
        global $wpdb;
        return $wpdb->prefix . 'gspin_shortcodes';
    }

    /**
     * Returns the database charset.
     * 
     * @since  2.0.12
     * @return string Database table name.
     */
    public function get_charset() {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }

    /**
     * Create database tables on plugin activation.
     * 
     * @since  2.0.12
     * @return void
     */
    public function migration() {
        $this->create_shortcodes_table();
    }

    /**
     * Creates a database table for storing shortcodes data.
     * 
     * @since  2.0.12
     * @return void
     */
    public function create_shortcodes_table() {
        $tableName = $this->get_shortcodes_table();
        $charset   = $this->get_charset();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
            shortcode_name TEXT NOT NULL,
            shortcode_settings LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )".$charset.";";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function migration_1_7_0() {

        global $wpdb;
        $shortcodes = plugin()->builder->fetch_shortcodes();

        // Update Shortcode Data
        foreach ( $shortcodes as $shortcode ) {

            if ( isset( $shortcode['userid'] ) && isset( $shortcode['count'] ) ) {

                $shortcode['shortcode_settings'] = json_decode( $shortcode['shortcode_settings'], true );
                
                $shortcode['shortcode_settings']['userid']                = $shortcode['userid'];
                $shortcode['shortcode_settings']['board_name']            = $shortcode['board_name'];
                $shortcode['shortcode_settings']['count']                 = $shortcode['count'];
                $shortcode['shortcode_settings']['show_pin_title']        = $shortcode['show_pin_title'];

                $shortcode['shortcode_settings']  = plugin()->builder->validate_shortcode_settings( $shortcode['shortcode_settings'] );
                
                $data = array( "shortcode_settings" => json_encode( $shortcode['shortcode_settings'] ) );
                
                $tableName = $this->get_shortcodes_table();
                $wpdb->update( $tableName, $data, array( 'id' => $shortcode['id'] ), $this->get_db_columns() );

                wp_cache_delete('gspin_shortcodes', 'gs_pinterest');
                
            }            
        }

        // Drop Unnecessary Columns
        $columns_to_drop = ['userid', 'board_name', 'count', 'show_pin_title'];
        $table_name = $this->get_shortcodes_table();
    
        foreach ( $columns_to_drop as $column ) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = %s AND table_name = %s AND column_name = %s",
                DB_NAME, $table_name, $column
            ));
            if ($exists) $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column");
        }

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
            'shortcode_settings' => '%s'
        );
    }

    public function maybe_upgrade_data( $old_version ) {

		if ( version_compare( $old_version, '1.4.0', '<') ) {
			$this->migration();
		}

		if ( version_compare( $old_version, '1.7.1', '<' ) ) {
			$this->migration_1_7_0();
		}

	}

}
