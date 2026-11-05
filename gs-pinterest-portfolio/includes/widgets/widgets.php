<?php

namespace GSPIN;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Widgets manager.
 */
class Widgets {

    /**
     * User meta holding when the Widgets screen upgrade prompt was dismissed.
     */
    const PROMPT_DISMISSED_META = 'gspin_widgets_prompt_dismissed';

    /**
     * Constructor of the class.
     * 
     * @since 2.0.8
     */
    public function __construct() {
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_prompt_assets' ) );
        add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_prompt_assets' ) );
        add_action( 'wp_ajax_gspin_dismiss_widgets_prompt', array( $this, 'dismiss_prompt' ) );
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

    /**
     * Checks if the upgrade prompt should show for the current user.
     * 
     * @since  1.9.5
     * @return bool
     */
    public function should_show_prompt() {
        if ( plugin()->helpers->is_pro_active() ) {
            return false;
        }

        $dismissed = (int) get_user_meta( get_current_user_id(), self::PROMPT_DISMISSED_META, true );

        return $dismissed < time() - 30 * DAY_IN_SECONDS;
    }

    /**
     * Displays the User Profile theme prompt below the Profile widget form.
     * 
     * @since  1.9.5
     * @return void
     */
    public function display_prompt() {
        if ( ! $this->should_show_prompt() ) {
            return;
        }

        printf(
            '<div class="gspin-widget-prompt" data-nonce="%1$s" data-ajaxurl="%2$s"><button type="button" class="gspin-widget-prompt--dismiss" aria-label="%3$s">&times;</button><p>%4$s</p><a class="button button-primary" href="%5$s" target="_blank" rel="noopener">%6$s</a></div>',
            esc_attr( wp_create_nonce( '_gspin_dismiss_widgets_prompt_gs_' ) ),
            esc_url( admin_url( 'admin-ajax.php' ) ),
            esc_attr__( 'Dismiss for 30 days', 'gs-pinterest' ),
            esc_html__( 'Want your Pinterest profile header above your pins on a page, not only in a sidebar? The User Profile theme is in Pro.', 'gs-pinterest' ),
            esc_url( pricing_url( 'widgets' ) ),
            esc_html__( 'See User Profile theme', 'gs-pinterest' )
        );
    }

    /**
     * Loads the prompt style and dismiss script on the Widgets screen and the Customizer.
     * 
     * @since  1.9.5
     * @param  string $hook Current admin page, empty in the Customizer.
     * @return void
     */
    public function enqueue_prompt_assets( $hook = '' ) {
        if ( 'customize_controls_enqueue_scripts' !== current_action() && 'widgets.php' !== $hook ) {
            return;
        }

        if ( ! $this->should_show_prompt() ) {
            return;
        }

        wp_enqueue_style( 'gspin-admin-style', GSPIN_PLUGIN_URI . '/assets/admin/css/gspin_admin_style.css', [], GSPIN_VERSION );
        wp_enqueue_script( 'jquery' );
        wp_add_inline_script( 'jquery-core', "jQuery( function( $ ) {
            $( document ).on( 'click', '.gspin-widget-prompt--dismiss', function() {
                var prompt = $( this ).closest( '.gspin-widget-prompt' );
                $.post( prompt.data( 'ajaxurl' ), { action: 'gspin_dismiss_widgets_prompt', _wpnonce: prompt.data( 'nonce' ) } );
                $( '.gspin-widget-prompt' ).remove();
            } );
        } );" );
    }

    /**
     * Hides the prompt for the current user for 30 days.
     * 
     * @since  1.9.5
     * @return void
     */
    public function dismiss_prompt() {
        check_ajax_referer( '_gspin_dismiss_widgets_prompt_gs_' );

        if ( ! current_user_can( 'edit_theme_options' ) ) {
            wp_send_json_error( __( 'Unauthorised Request', 'gs-pinterest' ), 401 );
        }

        update_user_meta( get_current_user_id(), self::PROMPT_DISMISSED_META, time() );

        wp_send_json_success();
    }
}