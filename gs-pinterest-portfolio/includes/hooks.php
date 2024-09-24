<?php 

namespace GSPIN;

class Hooks {

    /**
     * Class constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'activation_redirect' ) );
        add_action( 'in_admin_header',  [ $this, 'remove_pinterest_admin_notices' ], PHP_INT_MAX );
        add_action( 'init', [ $this, 'gs_pin_i18n' ] );
        add_action( 'init', [ $this, 'action_links' ] );
    }

    /**
     * Add action links
     *
     * @since 1.0.0
     */
    public function action_links() {
        if ( ! plugin()->helpers->is_pro_active() ) {
            add_filter( 'plugin_action_links_' . GSPIN_PLUGIN_BASENAME, array( $this, 'gs_pinterest_pro_link' ) );
        }
    }

    /**
     * Redirect to options page
     *
     * @since v1.0.0
     */
    public function activation_redirect() {
        if (get_option('gspin_activation_redirect', false)) {
            delete_option('gspin_activation_redirect');
            if(!isset($_GET['activate-multi'])) {
                wp_redirect("admin.php?page=gs-pinterest-plugins-help");
            }
        }
    }

    /**
     * Load plugin text domain
     *
     * @since 1.0.0
     */
    public function gs_pin_i18n() {
        load_plugin_textdomain( 'gs-pinterest', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Remove Pinterest admin notices
     * 
     * @since 2.0.8
     */
    public function remove_pinterest_admin_notices() {
        if ( isset( $_GET['page'] ) && in_array( $_GET['page'], [ 'gs-pinterest-license', 'gsp-pinterest-main', 'gs-pinterest-plugins-premium', 'gs-pinterest-plugins-lite', 'gs-pinterest-plugins-help' ] ) ) {
            remove_all_actions( 'network_admin_notices' );
            remove_all_actions( 'user_admin_notices' );
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }

    /**
     * Populate plugins pro package link.
     * 
     * @since 2.0.8
     */
    public function gs_pinterest_pro_link( $gsPin_links ) {
        $gsPin_links[] = '<a class="gs-pro-link" href="https://www.gsplugins.com/product/gs-pinterest-portfolio" target="_blank">Go Pro!</a>';
        $gsPin_links[] = '<a href="https://www.gsplugins.com/wordpress-plugins" target="_blank">GS Plugins</a>';
        return $gsPin_links;
    }
}