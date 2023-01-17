<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWoosb_Helper' ) ) {
	class WPCleverWoosb_Helper {
		protected static $instance = null;
		protected static $settings = array();
		protected static $localization = array();

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// settings
			self::$settings = (array) get_option( 'woosb_settings', [] );
			// localization
			self::$localization = (array) get_option( 'woosb_localization', [] );
		}

		public static function get_price( $product, $min_or_max = 'min' ) {
			if ( self::get_setting( 'bundled_price_from', 'sale_price' ) === 'regular_price' ) {
				if ( $product->is_type( 'variable' ) ) {
					if ( $min_or_max === 'max' ) {
						$price = $product->get_variation_regular_price( 'max' );
					} else {
						$price = $product->get_variation_regular_price( 'min' );
					}
				} else {
					$price = $product->get_regular_price();
				}
			} else {
				if ( $product->is_type( 'variable' ) ) {
					if ( $min_or_max === 'max' ) {
						$price = $product->get_variation_price( 'max' );
					} else {
						$price = $product->get_variation_price( 'min' );
					}
				} else {
					$price = $product->get_price();
				}
			}

			return apply_filters( 'woosb_get_price', $price, $product, $min_or_max );
		}

		public static function get_price_to_display( $product, $qty = 1, $min_or_max = 'min' ) {
			return apply_filters( 'woosb_get_price_to_display', (float) wc_get_price_to_display( $product, array(
				'price' => self::get_price( $product, $min_or_max ),
				'qty'   => $qty
			) ), $product, $qty, $min_or_max );
		}

		public static function clean_ids( $ids ) {
			return apply_filters( 'woosb_clean_ids', $ids );
		}

		public static function clean( $var ) {
			if ( is_array( $var ) ) {
				return array_map( array( __CLASS__, 'clean' ), $var );
			} else {
				return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
			}
		}

		public static function minify_items( $items ) {
			$minify_items = array();

			foreach ( $items as $item ) {
				if ( empty( $minify_items ) ) {
					$minify_items[] = $item;
				} else {
					$has_item = false;

					foreach ( $minify_items as $key => $minify_item ) {
						if ( ( $minify_item['id'] === $item['id'] ) && ( $minify_item['attrs'] === $item['attrs'] ) ) {
							$minify_items[ $key ]['qty'] += $item['qty'];
							$has_item                    = true;
							break;
						}
					}

					if ( ! $has_item ) {
						$minify_items[] = $item;
					}
				}
			}

			return apply_filters( 'woosb_minify_items', $minify_items, $items );
		}

		public static function get_settings() {
			return apply_filters( 'woosb_get_settings', self::$settings );
		}

		public static function get_setting( $name, $default = false ) {
			if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
				$setting = self::$settings[ $name ];
			} else {
				$setting = get_option( '_woosb_' . $name, $default );
			}

			return apply_filters( 'woosb_get_setting', $setting, $name, $default );
		}

		public static function localization( $key = '', $default = '' ) {
			$str = '';

			if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
				$str = self::$localization[ $key ];
			} elseif ( ! empty( $default ) ) {
				$str = $default;
			}

			return apply_filters( 'woosb_localization_' . $key, $str );
		}
	}

	return WPCleverWoosb_Helper::instance();
}