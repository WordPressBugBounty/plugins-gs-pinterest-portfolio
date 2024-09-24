<?php

namespace GSPIN;

// if direct access than exit the file.
defined('ABSPATH') || exit;

class Helpers {

    /**
     * Generates star ratings.
     * 
     * @since  2.0.8
     * @return html Generated rating stars html markup.
     */
    public function wp_star_rating( $args = array() ) {
        $defaults = array(
            'rating' => 0,
            'type'   => 'rating',
            'number' => 0,
        );

        $r = wp_parse_args( $args, $defaults );

        // Non-english decimal places when the $rating is coming from a string
        $rating = str_replace( ',', '.', $r['rating'] );

        // Convert Percentage to star rating, 0..5 in .5 increments
        if ( 'percent' == $r['type'] ) {
            $rating = round( $rating / 10, 0 ) / 2;
        }

        // Calculate the number of each type of star needed
        $full_stars  = floor( $rating );
        $half_stars  = ceil( $rating - $full_stars );
        $empty_stars = 5 -  $full_stars - $half_stars;

        if ( $r['number'] ) {
            $format = _n( '%1$s rating based on %2$s rating', '%1$s rating based on %2$s ratings', $r['number'] );
            $title = sprintf( $format, number_format_i18n( $rating, 1 ), number_format_i18n( $r['number'] ) );
        } else {
            $title = sprintf( __( '%s rating' ), number_format_i18n( $rating, 1 ) );
        }

        echo '<div class="star-rating" title="' . esc_attr( $title ) . '">';
            echo '<span class="screen-reader-text">' . $title . '</span>';
            echo str_repeat( '<div class="star star-full"></div>', $full_stars );
            echo str_repeat( '<div class="star star-half"></div>', $half_stars );
            echo str_repeat( '<div class="star star-empty"></div>', $empty_stars);
        echo '</div>';
    }

    /**
     * Returns each item columns.
     * 
     * @since 2.0.8
     * 
     * @param string $desktop         The option name.
     * @param string $tablet          Settings section name.
     * @param string $mobile_portrait The value to save.
     * @param string $mobile          The value to save.
     * 
     * @return string Item columns.
     */
    public function get_column_classes( $desktop = '3', $tablet = '4', $mobile_portrait = '6', $mobile = '12' ) {
        return sprintf('gs-col-lg-%s gs-col-md-%s gs-col-sm-%s gs-col-xs-%s', $desktop, $tablet, $mobile_portrait, $mobile );
    }

    /**
     * Checks for is plugin pro version active.
     * 
     * @since 2.0.8
     */
    public function is_pro_active() {
        if ( defined( 'GS_PINTEREST_LICENSE_STATUS' ) ) {
            $status = get_option( GS_PINTEREST_LICENSE_STATUS, 'invalid' );
            if ( $status === 'valid' ) return true;
        }
        return false;
    }

    /**
     * Retrives option from settings options.
     * 
     * @since  2.0.8
     * @return mixed option value.
     */
    public function get_settings_option( $option, $default = '' ) {
        $options = get_option( 'gs_pinterest_settings' );

        if ( isset( $options[ $option ] ) ) {
            return $options[ $option ];
        }
        return $default;
    }

    /**
     * Renders boards widget.
     * 
     * @since 2.0.8
     */
    public function boards_widget( $gspin_url, $gspin_label, $gspin_size, $gspin_cstm_sizes, $gspin_action ) {
        // Pinterest default "Square" size
        $gspin_scale_width  = 80;
        $gspin_scale_height = 540;
        $gspin_board_width  = 600;
        
        // Sidebar size
        if( $gspin_size == 'sidebar' ) {
            $gspin_scale_width  = 60;
            $gspin_scale_height = 800;
            $gspin_board_width  = 150;
        }
        
        // Header size
        if( $gspin_size == 'header' ) {
            $gspin_scale_width  = 115;
            $gspin_scale_height = 120;
            $gspin_board_width  = 900;
        }
        
        // Custom size
        if( $gspin_size == 'custom' ) {
            // Can't be blank & MUST need greater than minimum value by Pinterest to get output.
            $gspin_scale_width  = ( $gspin_cstm_sizes['width'] >= 60 ? $gspin_cstm_sizes['width'] : '' );
            $gspin_scale_height = ( $gspin_cstm_sizes['height'] >= 60 ? $gspin_cstm_sizes['height'] : '' );
            $gspin_board_width  = ( $gspin_cstm_sizes['board_width'] >= 130 ? $gspin_cstm_sizes['board_width'] : '' );
        }
        
        if( $gspin_action == 'embedUser' ) {
            $gspin_url = "http://www.pinterest.com/" . $gspin_url;
        }
        
        $output  = '<a data-pin-do="' . $gspin_action . '"';
        $output .= 'href="' . esc_attr( $gspin_url ) . '"';
        $output .= ( ! empty( $gspin_scale_width ) ? 'data-pin-scale-width="' . $gspin_scale_width . '"' : '' );
        $output .= ( ! empty( $gspin_scale_height ) ? 'data-pin-scale-height="' . $gspin_scale_height . '"' : '' );
        // if the board_width is empty then it has been set to 'auto' so we need to leave the data-pin-board-width attribute completely out
        $output .= ( ! empty( $gspin_board_width ) ? 'data-pin-board-width="' . $gspin_board_width . '"' : '' );
        $output .= '>' . $gspin_label . '</a>';
        
        return $output;
    }

    /**
     * Renders gs_board.
     * 
     * @since 2.0.8
     */
    public function gs_board( $gspin_url, $gspin_board_width  = 400, $gspin_scale_width  = 80, $gspin_scale_height = 320  ) {
              
        $output  = '<a data-pin-do="embedBoard"';
        $output .= 'href="' . esc_attr( $gspin_url ) . '"';
        $output .= ( ! empty( $gspin_scale_width ) ? 'data-pin-scale-width="' . $gspin_scale_width . '"' : '' );
        $output .= ( ! empty( $gspin_scale_height ) ? 'data-pin-scale-height="' . $gspin_scale_height . '"' : '' );
        // if the board_width is empty then it has been set to 'auto' so we need to leave the data-pin-board-width attribute completely out
        $output .= ( ! empty( $gspin_board_width ) ? 'data-pin-board-width="' . $gspin_board_width . '"' : '' );
        $output .= '></a>';
        
        return $output;
    }

    /**
     * Returns shortcodes as select option.
     * 
     * @since 2.0.8
     * @param boolean $options If we want's the value as options.
     * @param boolean $default If we want's the value as the default option.
     * 
     * @return mixed Options array or the default value.
     */
    public function get_shortcode_as_options() {
        if ( ! is_db_table_there() ) {
            return;
        }

        $shortcodes = plugin()->builder->get_shortcodes_as_list();
        if ( empty( $shortcodes ) ) {
            return;
        }

        return array_combine(
            wp_list_pluck( plugin()->builder->get_shortcodes_as_list(), 'id' ),
            wp_list_pluck( plugin()->builder->get_shortcodes_as_list(), 'shortcode_name' )
        );

    }

    /**
     * Returns excerpt from given content.
     * 
     * @param string  $content    The large content.
     * @param integer $characters Character counts as excerpt.
     * 
     * @since  2.0.9
     * @return string
     */
    public function get_excerpt( $content, $characters = 100 ) {

        if ( $characters > 0 && strlen($content) > $characters ) {
            $content = substr( $content, 0, $characters );
        }

		return $content;
	}

    /**
     * Returns generated image with attributes.
     * 
     * @since 2.0.8
     */
    public function get_pin_thumbnail( $src, $alt, $extraClasses = [] ) {
        $disable = wp_validate_boolean( plugin()->builder->get( 'disable_lazy_load' ) );
        $classes = array_merge( [], $extraClasses );

        if ( $disable ) {
            $classes[] = plugin()->builder->get( 'lazy_load_class' );
        }

        $classes = apply_filters( 'gspin_thumnail_class', $classes );

        return sprintf( '<img src="%s" alt="%s" class="%s" />', $src, $alt, implode( ' ', $classes ) );
    }

    /**
     * Responsible for picking the provided array values from an associative array with key value pairs.
     * 
     * @param array $array The array we want to filter.
     * @param array $fields Fields of the array we want in the return.
     * 
     * @since  2.0.9
     * @return array The filtered array.
     */
    public function pluck( $array, $count, $fields = [] ) {
        $results = [];
        $index   = 0;
    
        foreach( $array as $item ) {
            if ( $index < $count ) {
                if ( empty( $fields ) ) {
                    $results[] = $item;
                } else {
                    foreach ( $fields as $pick ) {
                        if ( is_array( $item ) && isset( $item[$pick] ) ) {
                            $results[$index][$pick] = $item[$pick];
                        }
            
                        if ( is_object( $item ) && property_exists( $item, $pick) ) {
                            $results[$index][$pick] = $item->{$pick};
                        }
                    }
                }
            }

            $index++;
        }
    
        return $results;
    }

    function is_preview() {
        return isset( $_REQUEST['gspin_shortcode_preview'] ) && ! empty( $_REQUEST['gspin_shortcode_preview'] );
    }

}