<?php

namespace Minimog\Woo;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WPCleverWoosb' ) ) {
	class Product_Bundle extends \WPCleverWoosb {
		protected static $instance = null;

		const MINIMUM_PLUGIN_VERSION   = '6.2.0';
		const RECOMMEND_PLUGIN_VERSION = '6.4.4';

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Init self constructor to avoid auto call parent::__construct
		 * This make code run twice times.
		 */
		public function __construct() {
		}

		public function initialize() {
			if ( ! $this->is_activate() ) {
				return;
			}

			if ( defined( 'WOOSB_VERSION' ) ) {
				if ( version_compare( WOOSB_VERSION, self::MINIMUM_PLUGIN_VERSION, '<' ) ) {
					return;
				}

				if ( version_compare( WOOSB_VERSION, self::RECOMMEND_PLUGIN_VERSION, '<' ) ) {
					add_action( 'admin_notices', [ $this, 'admin_notice_recommend_plugin_version' ] );
				}
			}

			minimog_remove_filters_for_anonymous_class( 'woocommerce_woosb_add_to_cart', 'WPCleverWoosb', 'add_to_cart_form' );
			add_action( 'woocommerce_woosb_add_to_cart', [ $this, 'add_to_cart_form' ] );
		}

		public function is_activate() {
			return class_exists( 'WPCleverWoosb' );
		}

		public function admin_notice_recommend_plugin_version() {
			minimog_notice_required_plugin_version( 'WPC Product Bundles for WooCommerce', self::RECOMMEND_PLUGIN_VERSION );
		}

		public function get_types() {
			return self::$types;
		}

		public function add_to_cart_form() {
			/**
			 * @var \WC_Product
			 */
			global $product;

			if ( ! $product || ! $product->is_type( 'woosb' ) ) {
				return;
			}

			if ( $product->has_variables() ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );
			}

			if ( ( get_option( '_woosb_bundled_position', 'above' ) === 'above' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				$this->minimog_show_bundled();
			}

			wc_get_template( 'single-product/add-to-cart/simple.php' );

			if ( ( get_option( '_woosb_bundled_position', 'above' ) === 'below' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				$this->minimog_show_bundled();
			}
		}

		/**
		 * @param \WC_Product_Woosb $product
		 */
		function minimog_show_bundled( $product = null ) {
			if ( ! $product ) {
				global $product;
			}

			if ( ! $product || ! $product->is_type( 'woosb' ) ) {
				return;
			}

			$product_id          = $product->get_id();
			$fixed_price         = $product->is_fixed_price();
			$discount_amount     = $product->get_discount_amount();
			$discount_percentage = $product->get_discount_percentage();
			$order               = 1;
			$quantity_input_html = '';

			if ( $items = $product->get_items() ) {
				do_action( 'woosb_before_wrap', $product );

				echo '<div class="woosb-wrap woosb-bundled" data-id="' . esc_attr( $product_id ) . '">';

				if ( $before_text = apply_filters( 'woosb_before_text', get_post_meta( $product_id, 'woosb_before_text', true ), $product_id ) ) {
					echo '<div class="woosb-before-text woosb-text">' . do_shortcode( stripslashes( $before_text ) ) . '</div>';
				}

				do_action( 'woosb_before_table', $product );
				?>
				<div class="woosb-products"
				     data-product-sku="<?php echo esc_attr( $product->get_sku() ); ?>"
				     data-discount-amount="<?php echo esc_attr( $discount_amount ); ?>"
				     data-discount="<?php echo esc_attr( $discount_percentage ); ?>"
				     data-fixed-price="<?php echo esc_attr( $fixed_price ? 'yes' : 'no' ); ?>"
				     data-price="<?php echo esc_attr( wc_get_price_to_display( $product ) ); ?>"
				     data-price-suffix="<?php echo esc_attr( htmlentities( $product->get_price_suffix() ) ); ?>"
				     data-variables="<?php echo esc_attr( $product->has_variables() ? 'yes' : 'no' ); ?>"
				     data-optional="<?php echo esc_attr( $product->is_optional() ? 'yes' : 'no' ); ?>"
				     data-min="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ? : 1 ); ?>"
				     data-max="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ? : '' ); ?>">

					<?php
					foreach ( $items as $item ) {
						/**
						 * @var \WC_Product_Variable $_product
						 */
						$_product = wc_get_product( $item['id'] );

						if ( ! $_product || in_array( $_product->get_type(), $this->get_types(), true ) ) {
							continue;
						}

						if ( ! apply_filters( 'woosb_item_exclude', true, $_product, $product ) ) {
							continue;
						}

						$_qty = $item['qty'];
						$_min = 0;
						$_max = 1000;

						if ( $product->is_optional() ) {
							if ( get_post_meta( $product_id, 'woosb_limit_each_min_default', true ) === 'on' ) {
								$_min = $_qty;
							} else {
								$_min = absint( get_post_meta( $product_id, 'woosb_limit_each_min', true ) ? : 0 );
							}

							$_max = absint( get_post_meta( $product_id, 'woosb_limit_each_max', true ) ? : 10000 );

							if ( ( $max_purchase = $_product->get_max_purchase_quantity() ) && ( $max_purchase > 0 ) && ( $max_purchase < $_max ) ) {
								// get_max_purchase_quantity can return -1
								$_max = $max_purchase;
							}

							if ( $_qty < $_min ) {
								$_qty = $_min;
							}

							if ( ( $_max > $_min ) && ( $_qty > $_max ) ) {
								$_qty = $_max;
							}
						}

						if ( ( ! $_product->is_in_stock() || ! $_product->has_enough_stock( $_qty ) ) && ( get_option( '_woosb_exclude_unpurchasable', 'no' ) === 'yes' ) ) {
							$_qty = 0;
						}

						if ( get_post_meta( $product_id, 'woosb_optional_products', true ) === 'on' ) {
							if ( ( $_product->get_backorders() === 'no' ) && ( $_product->get_stock_status() !== 'onbackorder' ) && is_int( $_product->get_stock_quantity() ) && ( $_product->get_stock_quantity() < $_max ) ) {
								$_max = $_product->get_stock_quantity();
							}

							if ( $_product->is_sold_individually() ) {
								$_max = 1;
							}

							ob_start();
							?>
							<div class="woosb-quantity">
								<?php
								if ( $_product->is_in_stock() ) {
									woocommerce_quantity_input( array(
										'input_value' => $_qty,
										'min_value'   => $_min,
										'max_value'   => $_max,
										'woosb_qty'   => array(
											'input_value' => $_qty,
											'min_value'   => $_min,
											'max_value'   => $_max,
										),
										'classes'     => array( 'input-text', 'woosb-qty', 'qty', 'text' ),
										'input_name'  => 'woosb_qty_' . $order // compatible with WPC Product Quantity
									), $_product );
								} else { ?>
									<input type="number" class="input-text qty text woosb-qty" value="0" disabled/>
								<?php } ?>
							</div>
							<?php
							$quantity_input_html = ob_get_clean();
						}

						$item_class = 'woosb-product';

						if ( ! apply_filters( 'woosb_item_visible', true, $_product, $product ) ) {
							$item_class .= ' woosb-product-hidden';
						}

						if ( ( ! $_product->is_in_stock() || ! $_product->has_enough_stock( $_qty ) || ! $_product->is_purchasable() ) && ( get_option( '_woosb_exclude_unpurchasable', 'no' ) === 'yes' ) ) {
							$_qty       = 0;
							$item_class .= ' woosb-product-unpurchasable';
						}

						do_action( 'woosb_above_item', $_product, $product, $order );
						?>
						<div class="<?php echo esc_attr( apply_filters( 'woosb_item_class', $item_class, $_product, $product, $order ) ); ?>"
						     data-name="<?php echo esc_attr( $_product->get_name() ); ?>"
						     data-id="<?php echo esc_attr( $_product->is_type( 'variable' ) ? 0 : $item['id'] ); ?>"
						     data-price="<?php echo esc_attr( \WPCleverWoosb_Helper::get_price_to_display( $_product ) ); ?>"
						     data-price-suffix="<?php echo esc_attr( htmlentities( $_product->get_price_suffix() ) ); ?>"
						     data-qty="<?php echo esc_attr( $_qty ); ?>" data-order="<?php echo esc_attr( $order ); ?>">
							<?php
							do_action( 'woosb_before_item', $_product, $product, $order );

							if ( get_option( '_woosb_bundled_thumb', 'yes' ) !== 'no' ) { ?>
								<div class="woosb-thumb">
									<?php if ( $_product->is_visible() && ( get_option( '_woosb_bundled_link', 'yes' ) !== 'no' ) ) {
										echo '<a ' . ( get_option( '_woosb_bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-btn no-ajaxy" data-id="' . $item['id'] . '"' : '' ) . ' href="' . esc_url( $_product->get_permalink() ) . '" ' . ( get_option( '_woosb_bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
									} ?>
									<?php
									/**
									 * Disabled variation image changed.
									 * Because it not support properly image size.
									 * Move img out of div to make js disabled.
									 */
									?>
									<!--<div class="woosb-thumb-ori"></div>
									<div class="woosb-thumb-new"></div>-->
									<?php
									$product_image = \Minimog_Woo::instance()->get_product_image( $_product, \Minimog_Woo::instance()->get_loop_product_image_size( 60 ) );
									echo apply_filters( 'woosb_item_thumbnail', $product_image, $_product );
									?>

									<?php if ( $_product->is_visible() && ( get_option( '_woosb_bundled_link', 'yes' ) !== 'no' ) ) {
										echo '</a>';
									} ?>
								</div><!-- /woosb-thumb -->
							<?php } ?>

							<div class="woosb-product-info">
								<div class="woosb-product-main-info">
									<div class="woosb-title-wrap">
										<?php
										do_action( 'woosb_before_item_name', $_product );

										echo '<h3 class="woosb-title post-title-2-rows">';

										if ( ( get_option( '_woosb_bundled_qty', 'yes' ) === 'yes' ) && ( get_post_meta( $product_id, 'woosb_optional_products', true ) !== 'on' ) ) {
											echo apply_filters( 'woosb_item_qty', $item['qty'] . ' × ', $item['qty'], $_product );
										}

										$_name         = '';
										$_product_name = apply_filters( 'woosb_item_product_name', $_product->get_name(), $_product );

										if ( $_product->is_visible() && ( get_option( '_woosb_bundled_link', 'yes' ) !== 'no' ) ) {
											$_name .= '<a ' . ( get_option( '_woosb_bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-btn no-ajaxy" data-id="' . $item['id'] . '"' : '' ) . ' href="' . esc_url( $_product->get_permalink() ) . '" ' . ( get_option( '_woosb_bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
										}

										if ( $_product->is_in_stock() && $_product->has_enough_stock( $_qty ) ) {
											$_name .= $_product_name;
										} else {
											$_name .= '<s>' . $_product_name . '</s>';
										}

										if ( $_product->is_visible() && ( get_option( '_woosb_bundled_link', 'yes' ) !== 'no' ) ) {
											$_name .= '</a>';
										}

										echo apply_filters( 'woosb_item_name', $_name, $_product, $product, $order );
										echo '</h3>';

										do_action( 'woosb_after_item_name', $_product );

										if ( get_option( '_woosb_bundled_description', 'no' ) === 'yes' ) {
											echo '<div class="woosb-description">' . apply_filters( 'woosb_item_description', $_product->get_short_description(), $_product ) . '</div>';
										}
										?>
									</div>

									<?php if ( ( $bundled_price = get_option( '_woosb_bundled_price', 'price' ) ) !== 'no' ) { ?>
										<div class="woosb-price">
											<div class="woosb-price-ori">
												<?php
												$_ori_price = $_product->get_price();
												$_get_price = \WPCleverWoosb_Helper::get_price( $_product );

												if ( ! $product->is_fixed_price() && $discount_percentage > 0 ) {
													$_new_price     = true;
													$_product_price = $_get_price * ( 100 - $discount_percentage ) / 100;
												} else {
													$_new_price     = false;
													$_product_price = $_get_price;
												}

												switch ( $bundled_price ) {
													case 'price':
														if ( $_new_price ) {
															$_price = wc_format_sale_price( wc_get_price_to_display( $_product, array( 'price' => $_get_price ) ), wc_get_price_to_display( $_product, array( 'price' => $_product_price ) ) );
														} else {
															if ( $_get_price > $_ori_price ) {
																$_price = wc_price( \WPCleverWoosb_Helper::get_price_to_display( $_product ) ) . $_product->get_price_suffix();
															} else {
																$_price = $_product->get_price_html();
															}
														}

														break;
													case 'subtotal':
														if ( $_new_price ) {
															$_price = wc_format_sale_price( wc_get_price_to_display( $_product, array(
																	'price' => $_get_price,
																	'qty'   => $item['qty'],
																) ), wc_get_price_to_display( $_product, array(
																	'price' => $_product_price,
																	'qty'   => $item['qty'],
																) ) ) . $_product->get_price_suffix();
														} else {
															$_price = wc_price( \WPCleverWoosb_Helper::get_price_to_display( $_product, $item['qty'] ) ) . $_product->get_price_suffix();
														}

														break;
													default:
														$_price = $_product->get_price_html();
												}

												echo apply_filters( 'woosb_item_price', $_price, $_product );
												?>
											</div>
											<div class="woosb-price-new"></div>
											<?php do_action( 'woosb_after_item_price', $_product ); ?>
										</div>
									<?php } ?>
								</div>
								<div class="woosb-product-cart">
									<?php if ( $_product->is_type( 'variable' ) ) : ?>
										<div class="minimog-variation-select-wrap">
											<?php
											if ( ( get_option( '_woosb_variations_selector', 'default' ) === 'wpc_radio' ) && class_exists( 'WPClever_Woovr' ) ) {
												\WPClever_Woovr::woovr_variations_form( $_product );
											} else {
												\Minimog_Woo::instance()->get_product_variation_dropdown_html( $_product, [
													'show_label' => false,
													'show_price' => false,
												] );

												$attributes           = $_product->get_variation_attributes();
												$available_variations = $_product->get_available_variations();
												$variations_json      = wp_json_encode( $available_variations );
												$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

												if ( ! empty( $attributes ) ) {
													$total_attrs = count( $attributes );
													$loop_count  = 0;

													echo '<div class="variations_form" data-product_id="' . absint( $_product->get_id() ) . '" data-product_variations="' . $variations_attr . '">';
													echo '<div class="variations">';

													foreach ( $attributes as $attribute_name => $options ) {
														$loop_count++;
														?>
														<div class="variation">
															<div class="label">
																<?php echo wc_attribute_label( $attribute_name ); ?>
															</div>
															<div class="select">
																<?php
																$attr     = 'attribute_' . sanitize_title( $attribute_name );
																$selected = isset( $_REQUEST[ $attr ] ) ? wc_clean( stripslashes( urldecode( $_REQUEST[ $attr ] ) ) ) : $_product->get_variation_default_attribute( $attribute_name );
																wc_dropdown_variation_attribute_options( array(
																	'options'          => $options,
																	'attribute'        => $attribute_name,
																	'product'          => $_product,
																	'selected'         => $selected,
																	'show_option_none' => wc_attribute_label( $attribute_name ),
																) );
																?>
															</div>
															<?php if ( $loop_count === $total_attrs ): ?>
																<?php echo '<div class="reset">' . apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'minimog' ) . '</a>' ) . '</div>'; ?>
															<?php endif; ?>
														</div>
													<?php }

													echo '</div>';
													echo '</div>';

													if ( get_option( '_woosb_bundled_description', 'no' ) === 'yes' ) {
														echo '<div class="woosb-variation-description"></div>';
													}
												}
											}

											do_action( 'woosb_after_item_variations', $_product );
											?>
										</div>
									<?php endif; ?>
									<?php echo '' . $quantity_input_html; ?>
									<?php echo '<div class="woosb-availability">' . wc_get_stock_html( $_product ) . '</div>'; ?>
								</div>
							</div>
							<?php do_action( 'woosb_after_item', $_product, $product, $order ); ?>
						</div>
						<?php
						do_action( 'woosb_under_item', $_product, $product, $order );
						$order++;
					}
					?>
				</div>
				<?php
				if ( ! $product->is_fixed_price() && ( $product->has_variables() || $product->is_optional() ) ) {
					echo '<div class="woosb-total woosb-text"></div>';
				}

				echo '<div class="woosb-alert woosb-text" style="display: none"></div>';

				do_action( 'woosb_after_table', $product );

				if ( $after_text = apply_filters( 'woosb_after_text', get_post_meta( $product_id, 'woosb_after_text', true ), $product_id ) ) {
					echo '<div class="woosb-after-text woosb-text">' . do_shortcode( stripslashes( $after_text ) ) . '</div>';
				}

				echo '</div>';

				do_action( 'woosb_after_wrap', $product );
			}
		}
	}

	Product_Bundle::instance()->initialize();
}

