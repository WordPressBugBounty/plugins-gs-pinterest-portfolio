<?php

namespace GSPIN;

/**
 * Protect direct access
 */
if (!defined('ABSPATH')) exit;

function is_divi_active() {
    if (!defined('ET_BUILDER_PLUGIN_ACTIVE') || !ET_BUILDER_PLUGIN_ACTIVE) return false;
    return et_core_is_builder_used_on_current_request();
}

function is_divi_editor() {
    if ( !empty($_POST['action']) && $_POST['action'] == 'et_pb_process_computed_property' && !empty($_POST['module_type']) && $_POST['module_type'] == 'divi_gs_pinterest' ) return true;
}

function minimize_css_simple($css) {
    // https://datayze.com/howto/minify-css-with-php
    $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    return $css;
}

add_action( 'init', function() {
	if ( version_compare( GSPIN_VERSION, '1.4.4', '>' ) && defined( 'GSPIN_PRO_VERSION' ) && ! version_compare( GSPIN_PRO_VERSION, '2.0.9', '>' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin = 'gs-pinterest-portfolio-pro/gs_pinterest_portfolio.php';

		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );

			add_action( 'admin_notices', function() {
				if ( current_user_can( 'install_plugins' ) ) {
					echo '<div class="gstesti-admin-notice updated" style="display: flex; align-items: center; padding-left: 0; border-left-color: #EF4B53">';
					echo '<h4 style="margin-left: 15px">' . __( 'Please update GS Pinterest Portfolio PRO and try to activate again.', 'gs-pinterest' ) . '</h4>';
					echo "</div>";
				}
			});
		}
	}
});

/**
 * Activation redirects
 *
 * @since v1.0.0
 */
register_activation_hook( GSPIN_PLUGIN_FILE, function() {
	add_option('gspin_activation_redirect', true);
});

/**
 * Remove Reviews Metadata on plugin Deactivation.
 */
register_deactivation_hook( GSPIN_PLUGIN_FILE, function() {
	delete_option('gspin_active_time');
	delete_option('gspin_maybe_later');
	delete_option('gsadmin_maybe_later');
});

/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_gs_pinterest_portfolio() {

    if ( ! class_exists( 'GSPinAppSero\Insights' ) ) {
      	require_once GSPIN_PLUGIN_DIR . '/appsero/Client.php';
    }

    $client = new \GSPinAppSero\Client( '2bf3f746-49ec-410b-91e2-b0362b5f669a', 'GS Pinterest Portfolio', GSPIN_PLUGIN_FILE );

    // Active insights
    $client->insights()->init();
}

if( !defined( 'GSPIN_PRO_VERSION' ) ) {
	appsero_init_tracker_gs_pinterest_portfolio();
}

function clear_schedule_event( $current, $updated = null ) {
	
	$should_clear = false;

	if ( empty($updated) ) {
		$should_clear = true;
	} 
	else if( !empty($current['userid']) !== !empty($updated['userid']) || !empty($current['board_name']) !== !empty($updated['board_name']) ) {
		$should_clear = true;
	}

	if( $should_clear ) {
		$args           =    array_filter( array( $current['userid'], $current['board_name'] ) );
		$get_event      =    wp_get_scheduled_event( 'gs_pinterest_pin_sync_event', $args );
		if( $get_event ) wp_clear_scheduled_hook( 'gs_pinterest_pin_sync_event', $args );

	}
}

function gspin_add_schedule_event( $username = '', $board = '' ) {

	$cron_interval = plugin()->builder->get( 'sync_interval', 'weekly' );
	$args = array_filter(array($username, $board));

	if ( $cron_interval !== 'never_sync' ) {
		if ( ! wp_next_scheduled('gs_pinterest_pin_sync_event', $args) ) {
			wp_schedule_event( time(), $cron_interval, 'gs_pinterest_pin_sync_event', $args );
		}
	}

}

function get_shortcodes() {
    return plugin()->builder->fetch_shortcodes(null, false, true);
}

function gs_is_pro_active() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    return is_plugin_active( 'gs-pinterest-portfolio-pro/gs_pinterest_portfolio.php' );
}