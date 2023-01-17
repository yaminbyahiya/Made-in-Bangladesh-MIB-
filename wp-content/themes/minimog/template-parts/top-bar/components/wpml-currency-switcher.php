<?php
/**
 * Currency switcher by WooCommerce Multilingual & Multicurrency plugin
 *
 * @package Minimog
 * @since   1.9.1
 * @version 1.9.1
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="currency-switcher-menu-wrap">
	<?php
	do_action( 'wcml_currency_switcher', array(
		'format'         => '%code%',
		'switcher_style' => 'wcml-dropdown',
	) );
	?>
</div>
