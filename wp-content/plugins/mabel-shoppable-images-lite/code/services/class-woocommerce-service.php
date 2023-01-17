<?php

namespace MABEL_SILITE\Code\Services
{
	class Woocommerce_Service
	{
		private static $display_options = null;

		public static function get_product($id)
		{
			$product = wc_get_product($id);
            if($product)
                return $product;
            return null;
		}

		public static function thing_to_html_attribute_string($thing) {

			$encoded = wp_json_encode($thing);
			return function_exists('wc_esc_json') ? wc_esc_json($encoded) : _wp_specialchars($encoded, ENT_QUOTES, 'UTF-8', true);

		}

		private static function get_price_display_options() {

			if(!self::$display_options) {

				self::$display_options = [
					'format'        => get_woocommerce_price_format(),
					'symbol'        => get_woocommerce_currency_symbol(),
					'decimals'      => wc_get_price_decimals(),
					'decimal'       => wc_get_price_decimal_separator(),
					'thousand'      => wc_get_price_thousand_separator()
				];

			}

			return self::$display_options;

		}

		public static function format_price($price) {

			$price_display_options = self::get_price_display_options();

			return sprintf(
				$price_display_options['format'],
				$price_display_options['symbol'],
				number_format(
					$price,
					$price_display_options['decimals'],
					$price_display_options['decimal'],
					$price_display_options['thousand']
				)
			);
		}

	}
}