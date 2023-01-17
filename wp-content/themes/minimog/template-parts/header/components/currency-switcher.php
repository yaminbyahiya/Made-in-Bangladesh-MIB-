<?php
/**
 * Currency switcher menu by CURCY - WooCommerce Multi Currency plugin
 *
 * @package Minimog
 * @since   1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var WOOMULTI_CURRENCY_F_Data $settings
 */
$settings = $args['settings'];

$currencies       = $settings->get_list_currencies();
$current_currency = $settings->get_current_currency();
$links            = $settings->get_links();
$currency_name    = get_woocommerce_currencies();
?>
<div class="currency-switcher-menu-wrap">
	<ul class="menu currency-switcher-menu woo-multi-currency-menu">
		<li class="menu-item-has-children">
			<a href="#">
				<span class="current-currency-text"><?php echo esc_html( $current_currency ); ?></span>
			</a>
			<ul class="sub-menu">
				<?php foreach ( $links as $code => $link ): ?>
					<?php
					if ( $code === $current_currency ) {
						continue;
					}

					if ( empty( $currency_name[ $code ] ) ) {
						continue;
					}

					$value   = esc_url( $link );
					$name    = $currency_name[ $code ];
					$current = '';
					?>
					<li>
						<a href="<?php echo esc_url( $value ) ?>"
						   class="<?php echo esc_attr( $current ); ?> currency-switcher-link">
							<span class="currency-text"><?php echo esc_html( $code ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</li>
	</ul>
</div>
