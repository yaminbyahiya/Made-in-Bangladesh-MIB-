<?php

namespace Minimog\Woo;

defined( 'ABSPATH' ) || exit;

class Free_Shipping_Label {
	protected static $instance = null;

	private $ignore_discounts = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function initialize() {
		add_action( 'woocommerce_before_cart', [ $this, 'output_cart_goal_html' ], 30 );
	}

	public function output_cart_goal_html() {
		$amount_for_free_shipping = $this->get_min_free_shipping_amount();
		$min_amount               = (float) $amount_for_free_shipping['amount'];
		$cart_total               = $this->get_cart_total();

		if ( $min_amount > 0 ) {
			$amount_left         = 0;
			$percent_amount_done = 100;

			if ( $cart_total < $min_amount ) {
				$amount_left         = $min_amount - $cart_total;
				$percent_amount_done = \Minimog_Helper::calculate_percentage( $cart_total, $min_amount );
			}

			$template_args = [
				'min_amount'          => $min_amount,
				'amount_left'         => $amount_left,
				'percent_amount_done' => $percent_amount_done,
				'cart_total'          => $cart_total,
			];

			wc_get_template( 'cart/cart-goal.php', $template_args );
		}
	}

	public function get_min_free_shipping_amount() {
		$is_available = false;
		// Automatic min amount.
		$min_free_shipping_amount = 0;
		$this->ignore_discounts   = false;

		if ( version_compare( WC()->version, '2.6.0', '<' ) ) {
			$free_shipping = new \WC_Shipping_Free_Shipping();
			if ( in_array( $free_shipping->requires, array( 'min_amount', 'either', 'both' ) ) ) {
				$min_free_shipping_amount = $free_shipping->min_amount;
			}
		} else {
			if (
				0 == $min_free_shipping_amount &&
				function_exists( 'WC' ) && ( $wc_shipping = WC()->shipping ) && ( $wc_cart = WC()->cart ) &&
				$wc_shipping->enabled &&
				( $packages = $wc_cart->get_shipping_packages() )
			) {
				$shipping_methods = $wc_shipping->load_shipping_methods( $packages[0] );
				foreach ( $shipping_methods as $shipping_method ) {
					if ( ! $shipping_method instanceof \WC_Shipping_Free_Shipping
					     || 'yes' !== $shipping_method->enabled
					     || 0 === $shipping_method->instance_id
					) {
						continue;
					}

					if ( in_array( $shipping_method->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
						if ( $shipping_method->is_available( $packages[0] ) ) {
							$is_available = true;
						}

						$this->ignore_discounts   = 'yes' === $shipping_method->ignore_discounts;
						$min_free_shipping_amount = $shipping_method->min_amount;
					}
				}
			}
		}

		return array(
			'amount'       => $min_free_shipping_amount,
			'is_available' => $is_available,
		);
	}

	/**
	 * @see \WC_Shipping_Free_Shipping::is_available()
	 */
	public function get_cart_total() {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) ) {
			return 0;
		}

		$total = WC()->cart->get_displayed_subtotal();

		if ( WC()->cart->display_prices_including_tax() ) {
			$total = $total - WC()->cart->get_discount_tax();
		}

		if ( ! $this->ignore_discounts ) {
			$total = $total - WC()->cart->get_discount_total();
		}

		/*
		$exclude_shipping       = false;
		$exclude_shipping_taxes = false;
		if ( $exclude_shipping ) {
			$shipping_taxes = $exclude_shipping_taxes ? WC()->cart->get_shipping_tax() : 0;
			$total          = $total - ( WC()->cart->get_shipping_total() + $shipping_taxes );
		}
		*/

		$total = round( $total, wc_get_price_decimals() );

		return (float) $total;
	}
}

Free_Shipping_Label::instance()->initialize();
