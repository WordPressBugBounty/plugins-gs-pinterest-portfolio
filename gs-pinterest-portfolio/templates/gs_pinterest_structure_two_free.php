<?php

namespace GSPIN;

if ( empty( $board_name ) ) {
	echo 'Please Provide Board Name';
	return;
}

$board_url = "https://www.pinterest.com/{$username}/{$board_name}";

?>

<div class="gspin-wrap gspin-widget gspin-board-widget">
	<?php echo plugin()->helpers->gs_board( $board_url, $settings['board_width'], $settings['pin_width'], $settings['pin_height'] ); ?>
</div>

<?php

wp_enqueue_script('pinterest-pinit-js');