<?php

namespace GSPIN;

// if direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * GS Pinterest Pin Widget
 */
class SinglePin extends \WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'gs_pinterest_widget', // Base ID
			__( 'GS Pinterest Pin Widget', 'gs-pinterest' ), // Name
			array( 'description' => __( 'Shows a single Pin', 'gs-pinterest' ), ) // Args
		);
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Pinterest Pin', 'gs-pinterest' );
        $pin_url     = ! empty( $instance['pin_url'] ) ? $instance['pin_url'] : '';
        $gspin_width = ! empty( $instance['gspin_width'] ) ? $instance['gspin_width'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'pin_url' ) ); ?>"><?php _e( 'Pin URL:'); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pin_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pin_url' ) ); ?>" type="text" value="<?php echo esc_attr( $pin_url ); ?>"
				placeholder="http://www.pinterest.com/pin/99360735500167749/" />
		</p>
		<p>
	      <label for="<?php echo esc_attr( $this->get_field_id( 'gspin_width' ) ); ?>">Pin Width: 
	        <select
				class='widefat'
				id="<?php echo esc_attr( $this->get_field_id( 'gspin_width' ) ); ?>"
	            name="<?php echo esc_attr( $this->get_field_name( 'gspin_width' ) ); ?>"
				type="text"
			>
				<option value=''<?php echo ( $gspin_width == '' ) ? 'selected' : ''; ?>><?php _e( 'Default', '' ); ?></option>
				<option value='medium'<?php echo ( $gspin_width == 'medium' ) ? 'selected' : ''; ?>><?php _e( 'Medium', '' ); ?></option> 
				<option value='large'<?php echo ( $gspin_width == 'large' ) ? 'selected' : ''; ?>><?php _e( 'Large', '' ); ?></option> 
	        </select>
	      </label>
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
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'single_pin_widget_title', $instance['title'] ). $args['after_title'];
		}

	    echo do_shortcode( '[gs_pin_widget pin_link="'.$instance['pin_url'].'" pin_width="'.$instance['gspin_width'].'"]' );
	 
	    echo $args['after_widget'];
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
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['pin_url'] = ( ! empty( $new_instance['pin_url'] ) ) ? strip_tags( $new_instance['pin_url'] ) : '';
        $instance['gspin_width'] = ( ! empty( $new_instance['gspin_width'] ) ) ? strip_tags( $new_instance['gspin_width'] ) : '';

		return $instance;
	}
}