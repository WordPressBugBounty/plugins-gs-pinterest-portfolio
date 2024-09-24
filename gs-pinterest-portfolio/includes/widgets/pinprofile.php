<?php

namespace GSPIN;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

class PinProfile extends \WP_Widget {

	/**
	 * Register Pinterest Profile Widget at admin
	 */
	public function __construct() {
		parent::__construct(
			'gs_pin_profile_widget', // Base ID
			__( 'GS Pinterest Profile Widget', 'gs-pinterest' ), // Name
			array(
				'description'	=>	__( 'Shows Pinterest Profile to any widget area.', 'gs-pinterest' ) ) // Args
		);
	}

	/**
	 * Back-end widget form for Pinterest Profile Widget
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$default = array(
			'title'               => '',
			'pin_username'        => '',
			'profile_widget_size' => 'square',
			'custom_width'        => '',
			'custom_height'       => '',
			'custom_board_width'  => ''
		);
		
		$instance = wp_parse_args( (array) $instance, $default );
		
		$title               = strip_tags( $instance['title'] );
		$pin_username        = strip_tags( $instance['pin_username'] );
		$profile_widget_size = strip_tags( $instance['profile_widget_size'] );
		// custom sizes
		$custom_width       = strip_tags( $instance['custom_width'] );
		$custom_height      = strip_tags( $instance['custom_height'] );
		$custom_board_width = strip_tags( $instance['custom_board_width'] );
		
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'gs-pinterest' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'pin_username' ) ); ?>"><?php _e( 'Pinterest User:', 'gs-pinterest' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pin_username' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pin_username' ) ); ?>" type="text" value="<?php echo esc_attr( $pin_username ); ?>" placeholder="pinterest" />
		</p>
		<?php
			$sizeOptions = apply_filters( 'gsp_widget_board_size', array(
				'square'    => __( 'Square', 'gs-pinterest' ),
				'disabled1' => __( 'Sidebar (Pro)', 'gs-pinterest' ),
				'disabled2' => __( 'Header (Pro)', 'gs-pinterest' ),
				'disabled3' => __( 'Custom (Pro)', 'gs-pinterest' ),
			));
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'profile_widget_size' ) ); ?>"><?php _e( 'Widget Size:', 'gs-pinterest' ); ?></label><br />
			<select name="<?php echo esc_attr( $this->get_field_name( 'profile_widget_size' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'profile_widget_size' ) ); ?>">
				<?php
                    if ( $sizeOptions ) {
                        foreach ( $sizeOptions as $key => $value ) {
                            $disabled = 'disabled1' === $key || 'disabled2' === $key || 'disabled3' === $key;
                            printf(
                                    '<option value="%s" %s %s>%s</option>', $key, selected( $instance['profile_widget_size'], $key, false ),
                                    $disabled ? 'disabled' : '', $value
                            );
                        }
                    }
                ?>
			</select>
		</p>
		<p>
			<?php _e( 'Following values only for \'Custom\' profile size', 'gs-pinterest' ); ?>:
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'custom_width' ) ); ?>"><?php _e( 'Image Width:', 'gs-pinterest' ); ?></label><br />
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'custom_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'custom_width' ) ); ?>" type="number" value="<?php echo esc_attr( $custom_width ); ?>" placeholder="min : 60" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'custom_height' ) ); ?>"><?php _e( 'Board Height:', 'gs-pinterest' ); ?></label><br />
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'custom_height' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'custom_height' ) ); ?>" type="number" value="<?php echo esc_attr( $custom_height ); ?>" placeholder="min : 60" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'custom_board_width' ) ); ?>"><?php _e( 'Board Width:', 'gs-pinterest' ); ?></label><br />
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'custom_board_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'custom_board_width' ) ); ?>" type="number" value="<?php echo esc_attr( $custom_board_width ); ?>" placeholder="min : 130" />
		</p>
		<?php
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		wp_enqueue_script('pinterest-pinit-js');
		extract( $args );

		$title               = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$pin_username        = ( ! empty( $instance['pin_username'] ) ? $instance['pin_username'] : 'pinterest' );
		$profile_widget_size = $instance['profile_widget_size'];
		$custom_sizes = array();

		if( 'custom' == $profile_widget_size ) {
			$custom_sizes = array( 
				'width'       => ( ! empty( $instance['custom_width'] ) ? $instance['custom_width'] : '' ),
				'height'      => ( ! empty( $instance['custom_height'] ) ? $instance['custom_height'] : '' ),
				'board_width' => ( ! empty( $instance['custom_board_width'] ) ? $instance['custom_board_width'] : '' )
			);
		}

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
        }

		$html = '<div class="gspin-wrap gspin-widget gspin-profile-widget">' . plugin()->helpers->boards_widget( $pin_username, '', $profile_widget_size, $custom_sizes, 'embedUser' ) . '</div>';
		
		do_action( 'gspin_profile_widget_before' );
		echo apply_filters( 'gspin_profile_widget_html', $html );
		do_action( 'gspin_profile_widget_after' );
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		// Update the form when saved
		$instance['title']               = strip_tags( $new_instance['title'] );
		$instance['pin_username']        = strip_tags( $new_instance['pin_username'] );
		$instance['profile_widget_size'] = $new_instance['profile_widget_size'];
		// Update custom size options
		$instance['custom_width']       = ( strip_tags( $new_instance['custom_width'] ) >= 60 ? $new_instance['custom_width'] : '' );
		$instance['custom_height']      = ( strip_tags( $new_instance['custom_height'] ) >= 60 ? $new_instance['custom_height'] : '' );
		$instance['custom_board_width'] = ( strip_tags( $new_instance['custom_board_width'] ) >= 130 ? $new_instance['custom_board_width'] : '' );

		return $instance;
	}
}