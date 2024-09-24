<?php
namespace GSPIN;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class Template_Loader {

    private static $plugin_template_path = '';

    private static $pro_plugin_template_path = '';

    private static $theme_path = '';

    private static $child_theme_path = '';

    public function __construct() {

        self::$plugin_template_path = GSPIN_PLUGIN_DIR . 'templates/';
        
        if ( gs_is_pro_active() ) {
            self::$pro_plugin_template_path = GSPIN_PRO_PLUGIN_DIR . 'templates/';
        }

        add_action( 'init', [$this, 'set_theme_template_path'] );

    }

    public function set_theme_template_path() {

        $dir = apply_filters( 'gs_pinterest_templates_folder', 'gs-pinterest' );

        if ( $dir ) {
            $dir = '/' . trailingslashit( ltrim( $dir, '/\\' ) );
            self::$theme_path = get_template_directory() . $dir;

            if ( is_child_theme() ) {
                self::$child_theme_path = get_stylesheet_directory() . $dir;
            }
        }

    }

    public static function locate_template( $template_file ) {
        
        // Default path
        $path = self::$plugin_template_path;
        
        // Check if requested file exist in plugin
        if ( ! empty(self::$pro_plugin_template_path) && file_exists( self::$pro_plugin_template_path . $template_file ) ) {
            $path = self::$pro_plugin_template_path;
        } else {
            if ( ! file_exists( $path . $template_file ) ) {
                return new WP_Error( 'gspin_template_not_found', __( 'Template file not found - GS Plugins', 'gs-pinterest' ) );
            }
        }

        // Override default template if exist from theme
        if ( file_exists( self::$theme_path . $template_file ) ) $path = self::$theme_path;
        
        if ( is_child_theme() ) {
            // Override default template if exist from child theme
            if ( file_exists( self::$child_theme_path . $template_file ) ) $path = self::$child_theme_path;
        }

        // Return template path, it can be default or overridden by theme
        return $path . $template_file;

    }

}