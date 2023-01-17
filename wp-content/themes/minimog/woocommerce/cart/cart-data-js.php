<?php
/**
 * Cart data using in JS
 */

defined( 'ABSPATH' ) || exit;

$qty = ! empty( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

$data = [
	'count' => $qty,
];
?>
<div class="cart-data-js">
	<div data-value="<?php echo esc_attr( wp_json_encode( $data ) ); ?>" class="cart-data-info"></div>
</div>
