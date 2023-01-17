<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Product_Woosb' ) && class_exists( 'WC_Product' ) ) {
	class WC_Product_Woosb extends WC_Product {
		protected $items = null;

		public function __construct( $product = 0 ) {
			$this->supports[] = 'ajax_add_to_cart';
			parent::__construct( $product );

			$this->build_items();
		}

		public function get_type() {
			return 'woosb';
		}

		public function add_to_cart_url() {
			$product_id = $this->id;

			if ( $this->is_purchasable() && $this->is_in_stock() && ! $this->has_variables() && ! $this->is_optional() ) {
				$url = remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $product_id ) );
			} else {
				$url = get_permalink( $product_id );
			}

			$url = apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );

			return apply_filters( 'woosb_product_add_to_cart_url', $url, $this );
		}

		public function add_to_cart_text() {
			if ( $this->is_purchasable() && $this->is_in_stock() ) {
				if ( ! $this->has_variables() && ! $this->is_optional() ) {
					$text = WPCleverWoosb_Helper::localization( 'button_add', esc_html__( 'Add to cart', 'woo-product-bundle' ) );
				} else {
					$text = WPCleverWoosb_Helper::localization( 'button_select', esc_html__( 'Select options', 'woo-product-bundle' ) );
				}
			} else {
				$text = WPCleverWoosb_Helper::localization( 'button_read', esc_html__( 'Read more', 'woo-product-bundle' ) );
			}

			$text = apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );

			return apply_filters( 'woosb_product_add_to_cart_text', $text, $this );
		}

		public function single_add_to_cart_text() {
			$text = WPCleverWoosb_Helper::localization( 'button_single', esc_html__( 'Add to cart', 'woo-product-bundle' ) );

			$text = apply_filters( 'woocommerce_product_single_add_to_cart_text', $text, $this );

			return apply_filters( 'woosb_product_single_add_to_cart_text', $text, $this );
		}

		public function is_on_sale( $context = 'view' ) {
			if ( ! $this->is_fixed_price() && ( $this->get_discount_amount() || $this->get_discount_percentage() ) ) {
				return true;
			}

			return parent::is_on_sale( $context );
		}

		public function get_sale_price( $context = 'view' ) {
			if ( ( $context === 'view' ) && ! $this->is_fixed_price() ) {
				$discount_amount     = $this->get_discount_amount();
				$discount_percentage = $this->get_discount_percentage();
				$discount            = $discount_amount || $discount_percentage;

				if ( $discount ) {
					$sale_price = 0;

					if ( $items = $this->items ) {
						foreach ( $items as $item ) {
							$_product = wc_get_product( $item['id'] );

							if ( ! $_product || $_product->is_type( 'woosb' ) ) {
								continue;
							}

							$_price = WPCleverWoosb_Helper::get_price( $_product ) * $item['qty'];

							if ( $discount_percentage ) {
								// when haven't discount_amount, apply the discount percentage
								$sale_price += round( $_price * ( 100 - $discount_percentage ) / 100, wc_get_price_decimals() );
							} else {
								$sale_price += $_price;
							}
						}
					}

					if ( $discount_amount ) {
						return $sale_price - $discount_amount;
					}

					return $sale_price;
				} else {
					return '';
				}
			}

			return parent::get_sale_price( $context );
		}

		public function get_price( $context = 'view' ) {
			if ( ( $context === 'view' ) && ( (float) $this->get_regular_price() == 0 ) ) {
				return '0';
			}

			if ( ( $context === 'view' ) && ( (float) parent::get_price( $context ) == 0 ) ) {
				return '0';
			}

			return parent::get_price( $context );
		}

		public function get_manage_stock( $context = 'view' ) {
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes';
			$parent_manage         = parent::get_manage_stock( $context );

			if ( ( $items = $this->items ) && ! $this->is_optional() ) {
				foreach ( $items as $item ) {
					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || $_product->is_type( 'woosb' ) || ( $exclude_unpurchasable && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) ) ) {
						continue;
					}

					if ( $_product->get_manage_stock( $context ) === true ) {
						return true;
					}

					if ( $_product->is_type( 'variation' ) ) {
						$_parent = wc_get_product( $_product->get_parent_id() );

						if ( $_parent->get_manage_stock( $context ) === true ) {
							return true;
						}
					}
				}

				if ( $this->is_manage_stock() ) {
					return $parent_manage;
				}

				return false;
			}

			return $parent_manage;
		}

		public function get_stock_status( $context = 'view' ) {
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes';
			$parent_status         = parent::get_stock_status( $context );

			if ( ( $items = $this->items ) && ! $this->is_optional() ) {
				$stock_status = 'instock';

				foreach ( $items as $item ) {
					$_id      = $item['id'];
					$_qty     = $item['qty'];
					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || $_product->is_type( 'woosb' ) || ( $exclude_unpurchasable && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) ) ) {
						continue;
					}

					$_min = absint( get_post_meta( $_id, 'woosb_limit_each_min', true ) ?: 0 );
					$_max = absint( get_post_meta( $_id, 'woosb_limit_each_max', true ) ?: 1000 );

					if ( $_qty < $_min ) {
						$_qty = $_min;
					}

					if ( ( $_max > $_min ) && ( $_qty > $_max ) ) {
						$_qty = $_max;
					}

					if ( ( $_product->get_stock_status( $context ) === 'outofstock' ) || ( ! $_product->has_enough_stock( $_qty ) ) ) {
						return 'outofstock';
					}

					if ( $_product->get_stock_status( $context ) === 'onbackorder' || ( $_product->get_stock_quantity() < $_qty && $_product->backorders_allowed() ) ) {
						$stock_status = 'onbackorder';
					}
				}

				if ( $this->is_manage_stock() ) {
					if ( $parent_status === 'instock' ) {
						return $stock_status;
					} else {
						return $parent_status;
					}
				}

				return $stock_status;
			}

			return $parent_status;
		}

		public function get_stock_quantity( $context = 'view' ) {
			$product_id            = $this->id;
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' );
			$parent_quantity       = parent::get_stock_quantity( $context );

			if ( ( $items = $this->items ) && ! $this->is_optional() ) {
				$available_qty = array();

				foreach ( $items as $item ) {
					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || $_product->is_type( 'woosb' ) || ! $_product->get_manage_stock() || ( $_product->get_stock_quantity() === null ) || ( ( $exclude_unpurchasable === 'yes' ) && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) ) ) {
						continue;
					}

					if ( $item['qty'] > 0 ) {
						$available_qty[] = floor( $_product->get_stock_quantity() / $item['qty'] );
					}
				}

				if ( count( $available_qty ) > 0 ) {
					sort( $available_qty );

					if ( $this->is_manage_stock() && ( $parent_quantity < $available_qty[0] ) ) {
						// update qty
						update_post_meta( $product_id, '_stock', $parent_quantity );

						return $parent_quantity;
					}

					// update qty
					update_post_meta( $product_id, '_stock', $available_qty[0] );

					return $available_qty[0];
				}
			}

			// update qty
			update_post_meta( $product_id, '_stock', $parent_quantity );

			return $parent_quantity;
		}

		public function get_backorders( $context = 'view' ) {
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' );
			$parent_backorders     = parent::get_backorders( $context );

			if ( ( $items = $this->items ) && ! $this->is_optional() ) {
				$backorders = 'yes';

				foreach ( $items as $item ) {
					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || $_product->is_type( 'woosb' ) || ! $_product->get_manage_stock() || ( ( $exclude_unpurchasable === 'yes' ) && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) ) ) {
						continue;
					}

					if ( $_product->get_backorders( $context ) === 'no' ) {
						return 'no';
					}

					if ( $_product->get_backorders( $context ) === 'notify' ) {
						$backorders = 'notify';
					}
				}

				if ( $this->is_manage_stock() ) {
					if ( $parent_backorders === 'yes' ) {
						return $backorders;
					} else {
						return $parent_backorders;
					}
				}

				return $backorders;
			}

			return $parent_backorders;
		}

		public function get_sold_individually( $context = 'view' ) {
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' );

			if ( ( $items = $this->items ) && ! $this->is_optional() ) {
				foreach ( $items as $item ) {
					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || $_product->is_type( 'woosb' ) || ( ( $exclude_unpurchasable === 'yes' ) && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) ) ) {
						continue;
					}

					if ( $_product->is_sold_individually() ) {
						return true;
					}
				}
			}

			return parent::get_sold_individually( $context );
		}

		// extra functions

		public function has_variables() {
			$has_variables = false;

			if ( $items = $this->items ) {
				foreach ( $items as $item ) {
					$_product = wc_get_product( $item['id'] );

					if ( $_product && $_product->is_type( 'variable' ) ) {
						$has_variables = true;
						break;
					}
				}
			}

			return apply_filters( 'woosb_has_variables', $has_variables, $this );
		}

		public function is_optional() {
			$product_id = $this->id;

			return apply_filters( 'woosb_is_optional', get_post_meta( $product_id, 'woosb_optional_products', true ) === 'on', $this );
		}

		public function is_manage_stock() {
			$product_id = $this->id;

			return apply_filters( 'woosb_is_manage_stock', get_post_meta( $product_id, 'woosb_manage_stock', true ) === 'on', $this );
		}

		public function is_fixed_price() {
			$product_id = $this->id;

			return apply_filters( 'woosb_is_fixed_price', get_post_meta( $product_id, 'woosb_disable_auto_price', true ) === 'on', $this );
		}

		public function get_discount_amount() {
			$product_id      = $this->id;
			$discount_amount = 0;

			// discount amount
			if ( ! $this->is_fixed_price() && ( $discount_amount = get_post_meta( $product_id, 'woosb_discount_amount', true ) ) ) {
				$discount_amount = (float) $discount_amount;
			}

			return apply_filters( 'woosb_get_discount_amount', $discount_amount, $this );
		}

		public function get_discount_percentage() {
			$product_id          = $this->id;
			$discount_percentage = 0;

			// discount percentage
			if ( ! $this->is_fixed_price() && ! $this->get_discount_amount() && ( $discount_percentage = get_post_meta( $product_id, 'woosb_discount', true ) ) && is_numeric( $discount_percentage ) && ( (float) $discount_percentage < 100 ) && ( (float) $discount_percentage > 0 ) ) {
				$discount_percentage = (float) $discount_percentage;
			}

			return apply_filters( 'woosb_get_discount_percentage', $discount_percentage, $this );
		}

		public function get_discount() {
			$discount = $this->get_discount_amount() ?: $this->get_discount_percentage() . '%';

			return apply_filters( 'woosb_get_discount', $discount, $this );
		}

		public function get_ids() {
			$product_id = $this->id;

			return apply_filters( 'woosb_get_ids', get_post_meta( $product_id, 'woosb_ids', true ), $this );
		}

		public function build_items( $ids = null ) {
			$items = array();

			if ( ! $ids ) {
				$ids = $this->get_ids();
			}

			if ( $ids ) {
				$ids_arr = explode( ',', $ids );

				if ( is_array( $ids_arr ) && count( $ids_arr ) > 0 ) {
					foreach ( $ids_arr as $ids_item ) {
						$data = explode( '/', $ids_item );
						$pid  = rawurldecode( isset( $data[0] ) ? $data[0] : 0 );

						if ( ! is_numeric( $pid ) ) {
							// sku
							$pid = wc_get_product_id_by_sku( ltrim( $pid, 'sku-' ) );
						}

						if ( $pid ) {
							$items[] = array(
								'id'    => apply_filters( 'woosb_item_id', $pid ),
								'qty'   => (float) ( isset( $data[1] ) ? $data[1] : 1 ),
								'attrs' => isset( $data[2] ) ? (array) json_decode( rawurldecode( $data[2] ) ) : array()
							);
						}
					}
				}
			}

			$this->items = $items;
		}

		public function get_items() {
			return apply_filters( 'woosb_get_items', $this->items, $this );
		}
	}
}