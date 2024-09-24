<?php

namespace GSPIN;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Gutenberg {

	private static $_instance = null;
        
    public static function get_instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;        
    }

    public function __construct() {
        add_action( 'init', [ $this, 'load_block_script' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );        
    }

    public function enqueue_block_editor_assets() {

        plugin()->scripts->wp_enqueue_style_all( 'public', ['gs_pinterest_public_divi'] );
        plugin()->scripts->wp_enqueue_script_all( 'public' );

    }

    public function load_block_script() {

        wp_add_inline_style( 'wp-block-editor', $this->get_block_css() );

        wp_register_script( 'gs-pinterest-block', GSPIN_PLUGIN_URI . '/includes/integrations/assets/gutenberg/gutenberg-widget.min.js', ['wp-blocks', 'wp-editor'], GSPIN_VERSION );
        
        $gs_pin_block = array(
            'select_shortcode'        => __( 'Select Shortcode', 'gs-pinterest' ),
            'edit_description_text'   => __( 'Edit this shortcode', 'gs-pinterest' ),
            'edit_link_text'          => __( 'Edit', 'gs-pinterest' ),
            'create_description_text' => __( 'Create new shortcode', 'gs-pinterest' ),
            'create_link_text'        => __( 'Create', 'gs-pinterest' ),
            'edit_link'               => admin_url( "admin.php?page=gsp-pinterest-main#/shortcode/" ),
            'create_link'             => admin_url( 'admin.php?page=gsp-pinterest-main#/shortcode' ),
            'shortcodes'              => $this->get_shortcode_list()
		);
		wp_localize_script( 'gs-pinterest-block', 'gs_pin_block', $gs_pin_block );

        register_block_type( 'gs-pin/pinshortcodeblock', array(
            'editor_script' => 'gs-pinterest-block',
            'attributes' => [
                'shortcodeID' => [
                    'type'    => 'string',
                    'default' => $this->get_default_item()
                ],
                'align' => [
                    'type'=> 'string',
                    'default'=> 'wide'
                ]
            ],
            'render_callback' => [$this, 'shortcodes_dynamic_render_callback']
        ));

    }

    public function shortcodes_dynamic_render_callback( $block_attributes ) {

        $shortcode_id = ( ! empty($block_attributes) && ! empty($block_attributes['shortcodeID']) ) ? absint( $block_attributes['shortcodeID'] ) : $this->get_default_item();
        return do_shortcode( sprintf( '[gs_pinterest id="%u"]', esc_attr($shortcode_id) ) );

    }

    public function get_block_css() {

        ob_start(); ?>
    
        .gs-pinterest--toolbar {
            padding: 20px;
            border: 1px solid #1f1f1f;
            border-radius: 2px;
        }

        .gs-pinterest--toolbar label {
            display: block;
            margin-bottom: 6px;
            margin-top: -6px;
        }

        .gs-pinterest--toolbar select {
            width: 250px;
            max-width: 100% !important;
            line-height: 42px !important;
        }

        .gs-pinterest--toolbar .gs-pin-block--des {
            margin: 10px 0 0;
            font-size: 16px;
        }

        .gs-pinterest--toolbar .gs-pin-block--des span {
            display: block;
        }

        .gs-pinterest--toolbar p.gs-pin-block--des a {
            margin-left: 4px;
        }
    
        <?php return ob_get_clean();
    
    }

    protected function get_shortcode_list() {

        return get_shortcodes();
    }

    protected function get_default_item() {
    
        $shortcodes = get_shortcodes();

        if ( !empty($shortcodes) ) {
            return $shortcodes[0]['id'];
        }

        return '';

    }

}