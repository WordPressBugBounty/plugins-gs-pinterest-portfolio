<?php 

/**
 *
 * @package   GS_Pinterest_Portfolio
 * @author    GS Plugins <hello@gsplugins.com>
 * @license   GPL-2.0+
 * @link      https://www.gsplugins.com/
 * @copyright 2016 GS Plugins
 *
 * @wordpress-plugin
 * Plugin Name:			GS Pinterest Portfolio Lite
 * Plugin URI:			https://www.gsplugins.com/product/gs-pinterest-portfolio/
 * Description:       	Responsive Pinterest plugin for WordPress to showcase your Pinterest Pins in a clean, modern layout. Display your pins anywhere on your site using shortcodes like [gs_pinterest id=1] or via widgets. Check <a href="https://pinterest.gsplugins.com/">GS Pinterest Porfolio Demo</a> and <a href="https://docs.gsplugins.com/gs-pinterest-portfolio/">Documentation</a>.
 * Version:           	1.9.1
 * Author:       		GS Plugins
 * Author URI:       	https://www.gsplugins.com/
 * Text Domain:       	gs-pinterest
 * License:           	GPL-2.0+
 * License URI:       	http://www.gnu.org/licenses/gpl-2.0.txt
 */

// if direct access than exit the file.
defined('ABSPATH') || exit;

/**
 * Defining constants
 */
define( 'GSPIN_VERSION', '1.9.1' );
define( 'GSPIN_MENU_POSITION', 31 );
define( 'GSPIN_PLUGIN_FILE', __FILE__ );
define( 'GSPIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GSPIN_PLUGIN_URI', plugins_url( '', __FILE__ ) );
define( 'GSPIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


add_action( 'plugins_loaded', 'gs_pinterest_bootstrap', 5 );

function gs_pinterest_bootstrap() {

    // Load textdomain first
    load_plugin_textdomain(
        'gs-pinterest',
        false,
        dirname( plugin_basename( GSPIN_PLUGIN_FILE ) ) . '/languages'
    );

    // Then include files
    require_once GSPIN_PLUGIN_DIR . 'includes/autoloader.php';
    require_once GSPIN_PLUGIN_DIR . 'includes/plugin.php';
}