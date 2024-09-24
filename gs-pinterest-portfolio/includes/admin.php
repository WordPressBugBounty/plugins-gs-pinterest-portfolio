<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

class Admin {
    
    /**
     * Class Constructor
     *
     * @since  2.0.8
     * @return void
     */
    public function __construct() {
        
        add_action( 'admin_menu', array( $this, 'menus' ) );
    }
    
    /**
     * Registers dashboard menus.
     * 
     * @since 
     */
    public function menus() {
        add_menu_page(
            __( 'GS Pinterest', 'gs-pinterest' ),
            __( 'GS Pinterest', 'gs-pinterest' ),
            'manage_options',
            'gsp-pinterest-main',
            array( $this, 'view' ),
            GSPIN_PLUGIN_URI . '/assets/img/icon.svg',
            GSPIN_MENU_POSITION
        );
    }

    /**
     * Includes view of shortcode builder.
     * 
     * @since  2.0.12
     * @return void
     */
    public function view() {
        include GSPIN_PLUGIN_DIR . 'includes/shortcode-builder/page.php';
    }

}
