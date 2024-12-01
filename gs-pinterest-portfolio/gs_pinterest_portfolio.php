<?php 

/**
 *
 * @package   GS_Pinterest_Portfolio
 * @author    GS Plugins <hello@gsplugins.com>
 * @license   GPL-2.0+
 * @link      https://www.gsplugins.com
 * @copyright 2016 GS Plugins
 *
 * @wordpress-plugin
 * Plugin Name:			GS Pins for Pinterest Lite
 * Plugin URI:			https://www.gsplugins.com/wordpress-plugins
 * Description:       	Best Responsive Pinterest plugin for WordPress to showcase Pinterest Pins. Display anywhere at your site using shortcodes like [gs_pinterest id=1] & widgets. Check <a href="https://pinterest.gsplugins.com">GS Pinterest Porfolio PRO Demo</a> and <a href="https://docs.gsplugins.com/gs-pinterest-portfolio">Documentation</a>.
 * Version:           	1.8.9
 * Author:       		GS Plugins
 * Author URI:       	https://www.gsplugins.com
 * Text Domain:       	gs-pinterest
 * License:           	GPL-2.0+
 * License URI:       	http://www.gnu.org/licenses/gpl-2.0.txt
 */

// if direct access than exit the file.
defined('ABSPATH') || exit;

/**
 * Defining constants
 */
define( 'GSPIN_VERSION', '1.8.9' );
define( 'GSPIN_MENU_POSITION', 31 );
define( 'GSPIN_PLUGIN_FILE', __FILE__ );
define( 'GSPIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GSPIN_PLUGIN_URI', plugins_url( '', __FILE__ ) );
define( 'GSPIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GSPIN_PLUGIN_DIR . 'includes/autoloader.php';
require_once GSPIN_PLUGIN_DIR . 'includes/plugin.php';
