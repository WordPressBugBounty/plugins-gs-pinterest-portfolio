<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

/**
 * Handles plugin all kind of migration.
 *
 * @since 1.3.0
 */
class Migration {

	/**
	 * Class constructor
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'plugin_update_version' ], 0 );
	}

	public function plugin_update_version() {

		$old_version = get_option('gspin_plugin_version', false);
    
        if (GSPIN_VERSION === $old_version) return;

		plugin()->db->maybe_upgrade_data( $old_version );

		gsPinterestGenerator()->assets_purge_all();
		update_option('gspin_plugin_version', GSPIN_VERSION);
		
    }
}
