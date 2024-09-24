<?php

namespace GSPIN;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Handle plugin notices.
 * 
 * @since 2.0.8
 */
class Notices {
    /**
     * Constructor of the class.
     * 
     * @since 2.0.8
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'review_notice' ) );
        if ( ! defined( 'GSPIN_PRO_VERSION' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice' ) );
        }
        add_action( 'admin_init', array( $this, 'nag_ignore' ) );
        add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );

        // add_action( 'admin_init', array( $this, 'gsadmin_signup_notice' ) );
    }

    /**
	 * Responsible for displaying review notice.
	 * 
	 * @since 2.0.8
	 */
    public function review_notice(){
        
        $this->dismiss();
        $this->pending();

        $activation_time  = get_site_option( 'gspin_active_time' );
        $review_dismissal = get_site_option( 'gspin_review_dismiss' );
        $maybe_later      = get_site_option( 'gspin_maybe_later' );

        if ( 'yes' == $review_dismissal ) {
            return;
        }

        if ( ! $activation_time ) {
            add_site_option( 'gspin_active_time', time() );
        }
        
        $daysinseconds = 259200; // 3 Days in seconds.
    
        if( 'yes' == $maybe_later ) {
            $daysinseconds = 604800 ; // 7 Days in seconds.
        }

        if ( time() - $activation_time > $daysinseconds ) {
            add_action( 'admin_notices', array( $this, 'notice_message' ) );
        }
    }

    /**
     * Displays notice message.
     * 
     * @since 2.0.8
     */
    public function notice_message(){
        $scheme      = ( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ) ) ? '&' : '?';
        $url         = $_SERVER['REQUEST_URI'] . $scheme . 'gspin_review_dismiss=yes';
        $dismiss_url = wp_nonce_url( $url, 'gspin-review-nonce' );
        $_later_link = $_SERVER['REQUEST_URI'] . $scheme . 'gspin_review_later=yes';
        $later_url   = wp_nonce_url( $_later_link, 'gspin-review-nonce' );
        ?>
        <div class="gspin-review-notice">
            <div class="gspin-review-thumbnail">
                <img src="<?php echo plugins_url('gs-pinterest-portfolio/assets/img/icon.svg') ?>" alt="">
            </div>
            <div class="gspin-review-text">
                <h3><?php _e( 'Leave A Review?', 'gs-pinterest' ) ?></h3>
                <p><?php _e( 'We hope you\'ve enjoyed using <b>GS Pinterest Portfolio</b>! Would you consider leaving us a review on WordPress.org?', 'gs-pinterest' ) ?></p>
                <ul class="gspin-review-ul">
                    <li>
                        <a href="https://wordpress.org/support/plugin/gs-pinterest-portfolio/reviews/" target="_blank">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e( 'Sure! I\'d love to!', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( $dismiss_url ); ?>">
                            <span class="dashicons dashicons-smiley"></span>
                            <?php _e( 'I\'ve already left a review', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( $later_url ); ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e( 'Maybe Later', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.gsplugins.com/contact/" target="_blank">
                            <span class="dashicons dashicons-sos"></span>
                            <?php _e( 'I need help!', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( $dismiss_url ); ?>">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e( 'Never show again', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>


        <style>
            .gspin-review-notice {
                padding: 15px 15px 15px 0;
                background-color: #fff;
                border-radius: 3px;
                margin: 20px 20px 0 0;
                border-left: 4px solid transparent;
            }

            .gspin-review-notice:after {
                content: '';
                display: table;
                clear: both;
            }

            .gspin-review-thumbnail {
                width: 114px;
                float: left;
                line-height: 80px;
                text-align: center;
                border-right: 4px solid transparent;
            }

            .gspin-review-thumbnail img {
                width: 72px;
                vertical-align: middle;
                opacity: .85;
                -webkit-transition: all .3s;
                -o-transition: all .3s;
                transition: all .3s;
            }

            .gspin-review-thumbnail img:hover {
                opacity: 1;
            }

            .gspin-review-text {
                overflow: hidden;
            }

            .gspin-review-text h3 {
                font-size: 24px;
                margin: 0 0 5px;
                font-weight: 400;
                line-height: 1.3;
            }

            .gspin-review-text p {
                font-size: 13px;
                margin: 0 0 5px;
            }

            .gspin-review-ul {
                margin: 0;
                padding: 0;
            }

            .gspin-review-ul li {
                display: inline-block;
                margin-right: 15px;
            }

            .gspin-review-ul li a {
                display: inline-block;
                color: #10738B;
                text-decoration: none;
                padding-left: 26px;
                position: relative;
            }

            .gspin-review-ul li a span {
                position: absolute;
                left: 0;
                top: -2px;
            }
        </style>
        <?php
    }

    /**
	 * Responsible for handling notice dismiss.
	 * 
	 * @since 2.0.8
	 */
    public function dismiss(){
        if ( ! is_admin() ||
            ! current_user_can( 'manage_options' ) ||
            ! isset( $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'gspin-review-nonce' ) ||
            ! isset( $_GET['gspin_review_dismiss'] ) ) {

            return;
        }

        add_site_option( 'gspin_review_dismiss', 'yes' );   
    }

    /**
	 * Responsible for handling notice mayble later option.
	 * 
	 * @since 2.0.8
	 */
    public function pending() {
        if ( ! is_admin() ||
            ! current_user_can( 'manage_options' ) ||
            ! isset( $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'gspin-review-nonce' ) ||
            ! isset( $_GET['gspin_review_later'] ) ) {

            return;
        }
        // Reset Time to current time.
        update_site_option( 'gspin_active_time', time() );
        update_site_option( 'gspin_maybe_later', 'yes' );
    }


    /**
     * Displays admin notices.
     * 
     * @since 2.0.8
     */
    public function admin_notice() {
        if ( current_user_can( 'install_plugins' ) ) {
            global $current_user ;
            $user_id = $current_user->ID;

            if ( ! get_user_meta( $user_id, 'gspin_ignore_notice279' ) ) {
                echo '<div class="gstesti-admin-notice updated" style="display: flex; align-items: center; padding-left: 0; border-left-color: #EF4B53"><p style="width: 32px;">';
                echo '<img style="width: 100%; display: block;"  src="' . plugins_url('gs-pinterest-portfolio/assets/img/icon.svg'). '" ></p><p> ';
                printf(__('<strong>GS Pinterest Portfolio</strong> now powering huge websites. Use the coupon code <strong>ENJOY25P</strong> to redeem a <strong>25&#37; </strong> discount on Pro. <a href="https://www.gsplugins.com/product/wordpress-pinterest-portfolio-plugin/" target="_blank" style="text-decoration: none;"><span class="dashicons dashicons-smiley" style="margin-left: 10px;"></span> Apply Coupon</a>
                <a href="%1$s" style="text-decoration: none; margin-left: 10px;"><span class="dashicons dashicons-dismiss"></span> I\'m good with free version</a>'), admin_url( 'admin.php?page=gs-pinterest-plugins-help&gspin_nag_ignore=0' ) );
                echo "</p></div>";
            }
        }
    }

    /**
     * Responsible for handling notice ignore.
     * 
     * @since 2.0.8
     */
    public function nag_ignore() {
        global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset( $_GET['gspin_nag_ignore'] ) && '0' == $_GET['gspin_nag_ignore'] ) {
            add_user_meta( $user_id, 'gspin_ignore_notice279', 'true', true );
        }
    }

    /**
     * Responsible for adding plugins row meta.
     * 
     * @since 2.0.8
     * 
     * @param array  $meta_fields Plugins meta data. including the version, author, author URI, and plugin URI.
     * @param string $file        Path to the plugin file relative to the plugins directory.
     * 
     * @return array Plugins meta data.       
     */
    public function row_meta( $meta_fields, $file ) {
        if ( $file != 'gs-pinterest-portfolio/gs_pinterest_portfolio.php' ) {
            return $meta_fields;
        }

        echo "<style>.gspin-rate-stars { display: inline-block; color: #ffb900; position: relative; top: 3px; }.gspin-rate-stars svg{ fill:#ffb900; } .gspin-rate-stars svg:hover{ fill:#ffb900 } .gspin-rate-stars svg:hover ~ svg{ fill:none; } </style>";
    
        $plugin_rate   = "https://wordpress.org/support/plugin/gs-pinterest-portfolio/reviews/?rate=5#new-post";
        $plugin_filter = "https://wordpress.org/support/plugin/gs-pinterest-portfolio/reviews/?filter=5";
        $svg_xmlns     = "https://www.w3.org/2000/svg";
        $svg_icon      = '';
    
        for ( $i = 0; $i < 5; $i++ ) {
            $svg_icon .= "<svg xmlns='" . esc_url( $svg_xmlns ) . "' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";
        }
    
        // Set icon for thumbsup.
        $meta_fields[] = '<a href="' . esc_url( $plugin_filter ) . '" target="_blank"><span class="dashicons dashicons-thumbs-up"></span>' . __( 'Vote!', 'gscs' ) . '</a>';
    
        // Set icon for 5-star reviews. v1.1.22
        $meta_fields[] = "<a href='" . esc_url( $plugin_rate ) . "' target='_blank' title='" . esc_html__( 'Rate', 'gscs' ) . "'><i class='gspin-rate-stars'>" . $svg_icon . "</i></a>";
    
        return $meta_fields;
    }

    /**
     * Responsible for displaying signup notice.
     * 
     * @since 2.0.8
     */
    public function gsadmin_signup_notice(){

        $this->gsadmin_signup_pending() ;
        $activation_time    = get_site_option( 'gsadmin_active_time' );
        $maybe_later        = get_site_option( 'gsadmin_maybe_later' );
    
        if ( ! $activation_time ) {
            add_site_option( 'gsadmin_active_time', time() );
        }
        
        if( 'yes' == $maybe_later ) {
            $daysinseconds = 604800 ; // 7 Days in seconds.
            if ( time() - $activation_time > $daysinseconds ) {
                add_action( 'admin_notices' , array( $this, 'gsadmin_signup_notice_message' ) );
            }
        }else{
            add_action( 'admin_notices' , array( $this, 'gsadmin_signup_notice_message' ) );
        }
    
    }

    /**
     * For the notice signup.
     */
    public function gsadmin_signup_notice_message(){
        $scheme      = (parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY )) ? '&' : '?';
        $_later_link = $_SERVER['REQUEST_URI'] . $scheme . 'gsadmin_signup_later=yes';
        $later_url   = wp_nonce_url( $_later_link, 'gsadmin-signup-nonce' );
        ?>
        <div class=" gstesti-admin-notice updated gspin-review-notice">
            <div class="gspin-review-text">
                <h3><?php _e( 'GS Plugins Affiliate Program is now LIVE!', 'gs-pinterest' ) ?></h3>
                <?php printf( '<p>Join GS Plugins affiliate program. Share our 80% OFF lifetime bundle deals or any plugin with your friends/followers and earn up to 50% commission. <a href="%s" target="_blank">%s</a></p>', 'https://www.gsplugins.com/affiliate-registration/?utm_source=wporg&utm_medium=admin_notice&utm_campaign=aff_regi', __( 'Click here to sign up.', 'gs-pinterest' ) ); ?>
                <ul class="gspin-review-ul">
                    <li style="display: inline-block;margin-right: 15px;">
                        <a href="<?php echo esc_url( $later_url ); ?>" style="display: inline-block;color: #10738B;text-decoration: none;position: relative;">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e( 'Hide Now', 'gs-pinterest' ) ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <?php
    }

    /**
     * For Maybe Later signup.
     */
    public function gsadmin_signup_pending() {
        if ( ! is_admin() ||
            ! current_user_can( 'manage_options' ) ||
            ! isset( $_GET['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'gsadmin-signup-nonce' ) ||
            ! isset( $_GET['gsadmin_signup_later'] ) ) {

            return;
        }
        // Reset Time to current time.
        update_site_option( 'gsadmin_maybe_later', 'yes' );
    }
}

