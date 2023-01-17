<?php
/**
 * Live View Visitors
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div id="live-viewing-visitors" class="live-viewing-visitors"
     data-settings="<?php echo esc_attr( json_encode( $settings ) ); ?>">
	<span class="icon minimog-animate-pulse far fa-eye"></span>
	<?php echo sprintf( esc_html__( '%s people are viewing this right now', 'minimog' ), '<span class="count">' . $total_visitors . '</span>' ); ?>
</div>
