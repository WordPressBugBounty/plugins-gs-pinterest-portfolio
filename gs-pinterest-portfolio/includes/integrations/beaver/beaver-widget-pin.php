<?php 
namespace GSPIN;

if ( class_exists( 'FLBuilder' ) ) {

    class GS_Beaver_Widget extends \FLBuilderModule {
    
        public function __construct() {
            
            parent::__construct(array(
                'name'            => __( 'GS Pinterest', 'gs-pinterest' ),
                'description'     => __( 'A totally awesome module!', 'gs-pinterest' ),
                'group'           => __( 'GS Plugins', 'gs-pinterest' ),
                'category'        => __( 'Basic', 'gs-pinterest' ),
                'dir'             => GSPIN_PLUGIN_DIR . 'includes/integrations/beaver/',
                'url'             => GSPIN_PLUGIN_URI . '/includes/integrations/beaver/',
                'icon'            => 'icon.svg',
                'editor_export'   => true, // Defaults to true and can be omitted.
                'enabled'         => true, // Defaults to true and can be omitted.
                'partial_refresh' => false, // Defaults to false and can be omitted.
            ));
            
        }

        public function get_icon( $icon = '' ) {

            $path = GSPIN_PLUGIN_DIR . 'assets/img/' . $icon;

            // check if $icon is referencing an included icon.
            if ( '' != $icon && file_exists( $path ) ) {
                $icon = file_get_contents( $path );
                return str_replace( ['width="40"', 'height="40"'], ['width="20"', 'height="20"'], $icon );
            }

            return '';
        }

    }

}
