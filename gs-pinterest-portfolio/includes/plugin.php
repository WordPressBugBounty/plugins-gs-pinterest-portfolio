<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

final class Plugin {

    /**
     * Holds the instance of the plugin currently in use.
     *
     * @since 2.0.8
     *
     * @var GSPIN\GSPIN
     */
    private static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    public $scripts;
    public $admin;
    public $shortcode;
    public $db;
    public $helpers;
    public $notices;
    public $widgets;
    public $integrations;
    public $migration;
    public $templateLoader;
    public $builder;
    public $pinterest;

    /**
     * Class Constructor
     *
     * @since  2.0.8
     * @return void
     */
    public function __construct() {

        require_once GSPIN_PLUGIN_DIR . 'includes/functions.php';
        
        $this->scripts        = new Scripts;
        $this->admin          = new Admin;
        $this->helpers        = new Helpers;
        $this->shortcode      = new Shortcode;
        $this->notices        = new Notices;
        $this->widgets        = new Widgets;
        $this->integrations   = new Integrations;
        $this->migration      = new Migration;
        $this->templateLoader = new Template_Loader;
        $this->builder        = new Builder;
        $this->pinterest      = new Pinterest;
        $this->db             = new Database;

        new Hooks();
        
        require_once GSPIN_PLUGIN_DIR . 'includes/asset-generator/gs-load-asset-generator.php';        
        require_once GSPIN_PLUGIN_DIR . 'includes/gs-common-pages/gs-pinterest-common-pages.php';
        
    }

}

function plugin() {
    return Plugin::get_instance();
}
plugin();