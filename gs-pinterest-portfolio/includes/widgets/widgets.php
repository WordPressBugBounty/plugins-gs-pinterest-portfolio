<?php

namespace GSPIN;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Widgets manager.
 */
class Widgets {

    /**
     * Constructor of the class.
     * 
     * @since 2.0.8
     */
    public function __construct() {
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
    }

    /**
     * Register all widgets.
     * 
     * @since  2.0.8
     * @return void
     */
    function register_widget() {
        register_widget( 'GSPIN\FollowPin' );
        register_widget( 'GSPIN\PinBoard' );
        register_widget( 'GSPIN\PinProfile' );
        register_widget( 'GSPIN\SinglePin' );
    }
}