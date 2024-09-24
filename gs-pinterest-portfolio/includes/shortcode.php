<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

/**
 * Responsible for the plugin shortcodes.
 * 
 * @since 2.0.8
 */
class Shortcode {

	/**
	 * Constructor of the class.
	 * 
	 * @since 2.0.8
	 */
	public function __construct() {
		add_shortcode( 'gs_pinterest', array( $this, 'pinterest_shortcode' ) );
		add_shortcode( 'gs_pin_widget', array( $this, 'pinWidget' ) );
		add_shortcode( 'gs_follow_pin_widget', array($this, 'followPinShortcodeWidget'));
	}

	/**
	 * Renders pinterest main shortcode.
	 * 
	 * @since 2.0.8
	 * 
	 * @param  array $atts Shortcode attributes.
	 * 
	 * @return html        Output markup.
	 */
	public function pinterest_shortcode($atts) {

		if ( empty($atts['id']) ) {
			return __('No shortcode ID found', 'gs-pinterest');
		}

		$shortcode_id = sanitize_key( $atts['id'] );
		$is_preview   = !empty( $atts['preview'] );


		$settings = (array) $this->get_shortcode_settings( $shortcode_id, $is_preview );

		// By default force mode
		$force_asset_load = true;

		if (!$is_preview) {

			// For Asset Generator
			$main_post_id = gsPinterestGenerator()->get_current_page_id();

			$asset_data = gsPinterestGenerator()->get_assets_data($main_post_id);

			if (empty($asset_data)) {
				// Saved assets not found
				// Force load the assets for first time load
				// Generate the assets for later use
				gsPinterestGenerator()->generate($main_post_id, $settings);
			} else {
				// Saved assets found
				// Stop force loading the assets
				// Leave the job for Asset Loader
				$force_asset_load = false;
			}
		}

		// responsive
		$desktop         = !empty($settings['columns']) ? $settings['columns'] : (($settings['cols']) ?? 3);
		$tablet          = !empty($settings['columns_tablet']) ? $settings['columns_tablet'] : (($settings['cols']) ?? 2);
		$mobile_portrait = !empty($settings['columns_mobile_portrait']) ? $settings['columns_mobile_portrait'] : (($settings['cols']) ?? 2);
		$columns_mobile  = !empty($settings['columns_mobile']) ? $settings['columns_mobile'] : (($settings['cols']) ?? 1);
		$columnClasses   = plugin()->helpers->get_column_classes($desktop, $tablet, $mobile_portrait, $columns_mobile);
		$fields          = ['id', 'description', 'images'];
		$gutter          = !empty($settings['gutter']) ? $settings['gutter'] : 10;
		$link_target     = !empty($settings['link_target']) ? $settings['link_target'] : '_blank';
		$gs_rss_pins 	 = [];
		$username 		 = $settings['userid'];
		$board_name 	 = $settings['board_name'];
		$count 	 		 = $settings['count'];
		$theme 	 		 = $settings['theme'];
		$show_pin_title  = wp_validate_boolean( $settings['show_pin_title'] );

		if ( ! plugin()->helpers->is_pro_active() ) {
			if ( ! in_array( $theme, [ 'gs_pin_theme1', 'gs_pin_theme_two' ] ) ) {
				$theme = 'gs_pin_theme1';
			}
		}

		if (empty($username)) {
			return esc_attr__('Pinterest username is invalid or empty', 'gs-pinterest');
		}

		if ('gs_pin_theme6' !== $theme) {
			$gs_rss_pins = plugin()->pinterest->get_board_pins_by_user($username, $board_name, $count, $fields);
		}

		ob_start();

		printf('<div id="gs_pin_area_%s" class="gs_pin_area %s" data-gutter="%s">', esc_attr( $atts['id'] ), esc_attr( $theme ), esc_attr( $settings['gutter'] ) );

		if ($theme === 'gs_pin_theme1') {
			$template = 'gs_pinterest_structure_one.php';
		}

		if ($theme === 'gs_pin_theme_two') {
			$template = 'gs_pinterest_structure_two_free.php';
		}

		if ( plugin()->helpers->is_pro_active() ) {
			
			if ('gs_pin_theme2' === $theme) {
				$template = 'gs_pinterest_structure_two.php';
			}

			if ('gs_pin_theme3' === $theme) {
				$template = 'gs_pinterest_structure_three_hov.php';
			}

			if ('gs_pin_theme4' === $theme) {
				$template = 'gs_pinterest_structure_four_pop.php';
			}

			if ('gs_pin_theme5' === $theme) {
				$template = 'gs_pinterest_structure_five_grey.php';
			}

			if ('gs_pin_theme6' === $theme) {
				$template = 'gs_pinterest_user_profile.php';
			}
		}

		include Template_Loader::locate_template($template);

		echo '</div>';

		if ( plugin()->integrations->is_builder_preview() || $force_asset_load ) {
			
			gsPinterestGenerator()->force_enqueue_assets($settings);
			wp_add_inline_script('gs_pinterest_public', "jQuery(document).trigger( 'gspin:scripts:reprocess' );jQuery(function() { jQuery(document).trigger( 'gspin:scripts:reprocess' ) })");
			
			// Shortcode Custom CSS
			$css = gsPinterestGenerator()->get_shortcode_custom_css( $settings );
			if ( !empty($css) ) printf( "<style>%s</style>" , minimize_css_simple($css) );
			
			// Prefs Custom CSS
			$css = gsPinterestGenerator()->get_prefs_custom_css();
			if ( !empty($css) ) printf( "<style>%s</style>" , minimize_css_simple($css) );

		}

		return ob_get_clean();
	}

	public function get_shortcode_settings( $id, $is_preview = false ) {

		$default_settings = array_merge( ['id' => $id, 'is_preview' => $is_preview], plugin()->builder->get_shortcode_default_settings() );

		if ( $is_preview ) return shortcode_atts( $default_settings, get_transient($id) );

		$shortcode = plugin()->builder->_get_shortcode($id);

		return shortcode_atts( $default_settings, (array) $shortcode['shortcode_settings'] );

	}

	public function getPinWidth( $pin_width = '' ) {
		if ( empty( $pin_width ) ) return '';
		if ( is_numeric($pin_width) ) {
			if ( $pin_width < 236 ) return 'small';
			if ( $pin_width < 346 ) return 'medium';
			return 'large';
		}
		return $pin_width;
	}

	/**
	 * Pin widget shortcode.
	 * 
	 * @since 2.0.8
	 * 
	 * @param  array $atts Shortcode attributes.
	 * @return html        Output markup.
	 */
	public function pinWidget($atts) {
		
		$atts = shortcode_atts([
			'pin_link'  => '',
			'pin_width' => 'small',
			'auto_play' => 'off',
			'repin_button' => 'on'
		], $atts );

		$pin_width = $this->getPinWidth( $atts['pin_width'] );

		$output = sprintf( '<div class="gs-pin-widget-area%s%s" style="%s"><a data-pin-do="embedPin" data-pin-width="%s" href="%s"></a></div>',
			wp_is_mobile() ? ' gs-pin--mobile' : '',
			sanitize_key( $atts['auto_play'] ) === 'on' ? ' gs-pin--autoplay' : '',
			is_numeric( $atts['pin_width'] ) ? 'min-width:160px;max-width:' . absint( $atts['pin_width'] ) . 'px;' : '',
			sanitize_text_field( $pin_width ),
			sanitize_url( $atts['pin_link'] )
		);

		add_action( 'wp_footer', [ $this, 'add_pinterest_script' ] );

		return $output;
	}

	/**
	 * Add Pinterest script.
	 * 
	 * @since 2.0.8
	 */
	public function add_pinterest_script() {

		wp_enqueue_script('jquery');
		wp_enqueue_script('pinterest-pinit-js');
		
		echo '<script async defer src="' . GSPIN_PLUGIN_URI . '/assets/js/gspin-widget.custom.js"></script>';

		echo '<style>.gs-pin-widget-area.gs-pin--autoplay.gs-pin--playing [data-pin-log="embed_story_play"] {display: none !important;}.gs-pin-widget-area.gs-pin--mobile [data-pin-log="embed_story_pause"] {display: none !important;}';

		$hide_repin_button = plugin()->builder->get('hide_repin_button');

		if ( $hide_repin_button ) {
			echo '.gs-pin-widget-area [data-pin-log="embed_pin_repin_large"],.gs-pin-widget-area [data-pin-log="embed_pin_repin_medium"],.gs-pin-widget-area [data-pin-log="embed_pin_repin"] {display: none !important}';
		}

		echo '</style>';
	}

	/**
	 * Follow Pin widget shortcode.
	 * 
	 * @since 2.0.8
	 * 
	 * @param  array $atts Shortcode attributes.
	 * @return html        Output markup.
	 */
	public function followPinShortcodeWidget($atts) {

		$atts = shortcode_atts(
			array(
				'pin_user'     => '',
				'follow_lebel' => ''
			),
			$atts
		);

		$pin_user     = sanitize_key( $atts['pin_user'] );
		$follow_lebel = sanitize_text_field( $atts['follow_lebel'] );

		$output = '';
		$output .= '<div class="pin-follow-widget-area">';
		$output .= sprintf(
			'<a data-pin-do="buttonFollow" href="https://www.pinterest.com/%s/">%s</a>',
			$pin_user,
			$follow_lebel
		);
		$output .= '</div>';
		return $output;
	}
}
