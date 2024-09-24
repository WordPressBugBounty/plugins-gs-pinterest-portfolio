<?php

namespace GSPIN;

/**
 * Template Name: Theme one
 * 
 * @since 1.4.4
 */
if ( ! empty( $gs_rss_pins ) ) : ?>
	<ul class="gs-pins">
		<?php foreach ( $gs_rss_pins as $gs_single_pin ) :
			$image = (array) $gs_single_pin['images'];
			?>
			<li class="gs-single-pin <?php echo esc_attr( $columnClasses ); ?>">
				<div class="gs-pin-details">
					<?php echo plugin()->helpers->get_pin_thumbnail( $image['564x']['url'], $gs_single_pin[ 'description' ] ); ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else: ?>
	<h4><?php echo __( "No pins found", 'gs-pinterest' ); ?></h4>
<?php endif; ?>