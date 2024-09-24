<?php
namespace GSPIN;
use GSPLUGINS\GS_Asset_Generator_Base;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class GS_Pinterest_Asset_Generator extends GS_Asset_Generator_Base {

	private static $instance = null;

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_assets_key() {
		return 'gs-pinterest-portfolio';
	}

	public function generateStyle( $selector, $selector_divi, $targets, $prop, $value ) {
		
		$selectors = [];

		if ( empty($targets) ) return;

		if ( gettype($targets) !== 'array' ) $targets = [$targets];

		if ( !empty($selector_divi) && ( is_divi_active() || is_divi_editor() ) ) {
			foreach ( $targets as $target ) $selectors[] = $selector_divi . $target;
		}

		foreach ( $targets as $target ) $selectors[] = $selector . $target;

		echo wp_strip_all_tags( sprintf( '%s{%s:%s}', join(',', $selectors), $prop, $value ) );

	}

	public function generateCustomCss( $settings, $shortCodeId ) {

		ob_start();

		$selector = '#gs_pin_area_' . $shortCodeId;
		$selector_divi = '#et-boc .et-l div ' . $selector;

		if ( isset($settings['gutter']) && $settings['gutter'] != '' ) {
			
			$margin = floatval( $settings['gutter'] ) / 2;
			$this->generateStyle( $selector, $selector_divi, [' .gs-pin-details', ' .gs-pin-pop'], 'margin', $margin . 'px' );

			if ( plugin()->helpers->is_pro_active() ) {
				$this->generateStyle( $selector, $selector_divi, ' .single-pin.gs-single-pin > img', 'padding', $margin . 'px' );
				$this->generateStyle( $selector, $selector_divi, ' .gspin-profile-title', 'padding-left', $margin . 'px' );
				$this->generateStyle( $selector, $selector_divi, ' .gspin-profile-title', 'padding-right', $margin . 'px' );
			}

		}

		return ob_get_clean();
	}

	public function generate_assets_data( Array $settings ) {

		if ( empty($settings) || !empty($settings['is_preview']) ) return;

		$this->add_item_in_asset_list( 'styles', 'gs_pinterest_public', ['gs-bootstrap-grid', 'gs-font-awesome'] );
		$this->add_item_in_asset_list( 'scripts', 'gs_pinterest_public', ['jquery', 'imagesloaded', 'gs-masonry'] );
		$this->add_item_in_asset_list( 'scripts', 'pinterest-pinit-js' );

		// Hooked for Pro
		do_action( 'gs_pin_assets_data_generated', $settings );

		if ( is_divi_active() ) {
			$this->add_item_in_asset_list( 'styles', 'gs_pinterest_public_divi', ['gs_pinterest_public'] );
		}

		$css = $this->get_shortcode_custom_css( $settings );
		if ( !empty($css) ) {
			$this->add_item_in_asset_list( 'styles', 'inline', minimize_css_simple($css) );
		}

	}

	public function is_builder_preview() {
		return plugin()->integrations->is_builder_preview();
	}

	public function enqueue_builder_preview_assets() {
		plugin()->scripts->wp_enqueue_style_all( 'public', ['gs_pinterest_public_divi'] );
		plugin()->scripts->wp_enqueue_script_all( 'public' );
		$this->enqueue_prefs_custom_css();
	}

	public function maybe_force_enqueue_assets( Array $settings ) {

		$exclude = ['gs_pinterest_public_divi'];
		if ( is_divi_active() ) $exclude = [];
		
		plugin()->scripts->wp_enqueue_style_all( 'public', $exclude );
		plugin()->scripts->wp_enqueue_script_all( 'public' );

		// Shortcode Generated CSS
		$css = $this->get_shortcode_custom_css( $settings );
		$this->wp_add_inline_style( $css );
		
		// Prefs Custom CSS
		$this->enqueue_prefs_custom_css();

	}

	public function get_shortcode_custom_css( $settings ) {
		return $this->generateCustomCss( $settings, $settings['id'] );
	}

	public function get_prefs_custom_css() {
		return minimize_css_simple( plugin()->builder->get( 'gspin_custom_css' ) );
	}

	public function enqueue_prefs_custom_css() {
		$this->wp_add_inline_style( $this->get_prefs_custom_css() );
	}

	public function wp_add_inline_style( $css ) {
		if ( !empty($css) ) $css = minimize_css_simple($css);
		if ( !empty($css) ) wp_add_inline_style( 'gs_pinterest_public', $css );
	}

	public function enqueue_plugin_assets( $main_post_id, $assets = [] ) {

		if ( empty($assets) || empty($assets['styles']) || empty($assets['scripts']) ) return;

		foreach ( $assets['styles'] as $asset => $data ) {
			if ( $asset == 'inline' ) {
				$this->wp_add_inline_style( $data );
			} else {
				Scripts::add_dependency_styles( $asset, $data );
			}
		}

		foreach ( $assets['scripts'] as $asset => $data ) {
			if ( $asset == 'inline' ) {
				if ( !empty($data) ) wp_add_inline_script( 'gs_pinterest_public', $data );
			} else {
				Scripts::add_dependency_scripts( $asset, $data );
			}
		}

		wp_enqueue_style( 'gs_pinterest_public' );
		wp_enqueue_script( 'gs_pinterest_public' );
		wp_enqueue_script( 'pinterest-pinit-js' );

		if ( is_divi_active() ) {
			wp_enqueue_style( 'gs_pinterest_public_divi' );
		}

		$this->enqueue_prefs_custom_css();
	}
}

if ( ! function_exists( 'gsPinterestGenerator' ) ) {
	function gsPinterestGenerator() {
		return GS_Pinterest_Asset_Generator::getInstance(); 
	}
}

// Must inilialized for the hooks
gsPinterestGenerator();