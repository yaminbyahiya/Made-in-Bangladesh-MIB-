<?php
/*
Plugin Name: WPC Frequently Bought Together for WooCommerce (Premium)
Plugin URI: https://wpclever.net/
Description: Increase your sales with personalized product recommendations.
Version: 4.5.3
Author: WPClever
Author URI: https://wpclever.net
Text Domain: woo-bought-together
Domain Path: /languages/
Requires at least: 4.0
Tested up to: 6.1
WC requires at least: 3.0
WC tested up to: 7.1
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WOOBT_VERSION' ) && define( 'WOOBT_VERSION', '4.5.3' );
! defined( 'WOOBT_FILE' ) && define( 'WOOBT_FILE', __FILE__ );
! defined( 'WOOBT_URI' ) && define( 'WOOBT_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WOOBT_DIR' ) && define( 'WOOBT_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WOOBT_SUPPORT' ) && define( 'WOOBT_SUPPORT', 'https://wpclever.net/support??utm_source=support&utm_medium=woobt&utm_campaign=wporg' );
! defined( 'WOOBT_REVIEWS' ) && define( 'WOOBT_REVIEWS', 'https://wordpress.org/support/plugin/woo-bought-together/reviews/?filter=5' );
! defined( 'WOOBT_CHANGELOG' ) && define( 'WOOBT_CHANGELOG', 'https://wordpress.org/plugins/woo-bought-together/#developers' );
! defined( 'WOOBT_DISCUSSION' ) && define( 'WOOBT_DISCUSSION', 'https://wordpress.org/support/plugin/woo-bought-together' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WOOBT_URI );

include 'includes/wpc-dashboard.php';
include 'includes/wpc-menu.php';
include 'includes/wpc-kit.php';
include 'includes/wpc-premium.php';

if ( ! function_exists( 'woobt_init' ) ) {
	add_action( 'plugins_loaded', 'woobt_init', 11 );

	function woobt_init() {
		// Load textdomain
		load_plugin_textdomain( 'woo-bought-together', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'woobt_notice_wc' );

			return;
		}

		if ( ! class_exists( 'WPCleverWoobt' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWoobt {
				protected static $instance = null;
				protected static $image_size = 'woocommerce_thumbnail';
				protected static $localization = array();
				protected static $settings = array();
				protected static $types = array(
					'simple',
					'variable',
					'variation',
					'woosb',
					'bundle',
					'subscription'
				);

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					// Get settings & localization
					self::$settings     = (array) get_option( 'woobt_settings', [] );
					self::$localization = (array) get_option( 'woobt_localization', [] );

					// Init
					add_action( 'init', [ $this, 'init' ] );

					// Add image to variation
					add_filter( 'woocommerce_available_variation', [ $this, 'available_variation' ], 10, 3 );

					// Settings
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );

					// Enqueue frontend scripts
					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

					// Enqueue backend scripts
					add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

					// Backend AJAX search
					add_action( 'wp_ajax_woobt_update_search_settings', [ $this, 'update_search_settings' ] );
					add_action( 'wp_ajax_woobt_get_search_results', [ $this, 'get_search_results' ] );

					// Shortcode
					add_shortcode( 'woobt', [ $this, 'shortcode' ] );
					add_shortcode( 'woobt_items', [ $this, 'shortcode' ] );

					// Product data tabs
					add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );

					// Product data panels
					add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
					add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );

					// Product price
					add_filter( 'woocommerce_product_price_class', [ $this, 'product_price_class' ] );

					// Add to cart button & form
					$position = apply_filters( 'woobt_position', self::get_setting( 'position', apply_filters( 'woobt_default_position', 'before' ) ) );

					if ( ( self::get_setting( 'atc_button', 'main' ) === 'main' ) && ( $position !== 'none' ) ) {
						add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_to_cart_button' ] );
					}

					switch ( $position ) {
						case 'before':
							add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'add_to_cart_form' ] );
							break;
						case 'after':
							add_action( 'woocommerce_after_add_to_cart_form', [ $this, 'add_to_cart_form' ] );
							break;
						case 'below_title':
							add_action( 'woocommerce_single_product_summary', [ $this, 'add_to_cart_form' ], 6 );
							break;
						case 'below_price':
							add_action( 'woocommerce_single_product_summary', [ $this, 'add_to_cart_form' ], 11 );
							break;
						case 'below_excerpt':
							add_action( 'woocommerce_single_product_summary', [ $this, 'add_to_cart_form' ], 21 );
							break;
						case 'below_meta':
							add_action( 'woocommerce_single_product_summary', [ $this, 'add_to_cart_form' ], 41 );
							break;
						case 'below_summary':
							add_action( 'woocommerce_after_single_product_summary', [ $this, 'add_to_cart_form' ], 9 );
							break;
						default:
							add_action( 'woobt_position_' . $position, [ $this, 'add_to_cart_form' ] );
					}

					// Add to cart
					add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [
						$this,
						'found_in_cart'
					], 10, 2 );
					add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 2 );
					add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
					add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 2 );
					add_filter( 'woocommerce_get_cart_item_from_session', [
						$this,
						'get_cart_item_from_session'
					], 10, 2 );

					// Add all to cart
					add_action( 'wp_ajax_woobt_add_all_to_cart', [ $this, 'add_all_to_cart' ] );
					add_action( 'wp_ajax_nopriv_woobt_add_all_to_cart', [ $this, 'add_all_to_cart' ] );

					// Cart contents
					add_action( 'woocommerce_before_mini_cart_contents', [ $this, 'before_mini_cart_contents' ], 10 );
					add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 9999 );

					// Cart item
					add_filter( 'woocommerce_cart_item_name', [ $this, 'cart_item_name' ], 10, 2 );
					add_filter( 'woocommerce_cart_item_price', [ $this, 'cart_item_price' ], 10, 2 );
					add_filter( 'woocommerce_cart_item_quantity', [ $this, 'cart_item_quantity' ], 10, 3 );
					add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 10, 2 );

					// Order item
					add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'order_line_item' ], 10, 3 );
					add_filter( 'woocommerce_order_item_name', [ $this, 'cart_item_name' ], 10, 2 );

					// Admin order
					add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'hidden_order_item_meta' ] );
					add_action( 'woocommerce_before_order_itemmeta', [ $this, 'before_order_item_meta' ], 10, 2 );

					// Order again
					add_filter( 'woocommerce_order_again_cart_item_data', [ $this, 'order_again_item_data' ], 10, 2 );
					add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'cart_loaded_from_session' ] );

					// Undo remove
					add_action( 'woocommerce_cart_item_restored', [ $this, 'cart_item_restored' ], 10, 2 );

					// Add settings link
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

					// Admin
					add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );

					// Search filters
					if ( self::get_setting( 'search_sku', 'no' ) === 'yes' ) {
						add_filter( 'pre_get_posts', [ $this, 'search_sku' ], 99 );
					}

					if ( self::get_setting( 'search_exact', 'no' ) === 'yes' ) {
						add_action( 'pre_get_posts', [ $this, 'search_exact' ], 99 );
					}

					if ( self::get_setting( 'search_sentence', 'no' ) === 'yes' ) {
						add_action( 'pre_get_posts', [ $this, 'search_sentence' ], 99 );
					}

					// WPML
					if ( function_exists( 'wpml_loaded' ) ) {
						add_filter( 'woobt_item_id', [ $this, 'wpml_item_id' ], 99 );
					}

					// Admin product filter
					add_filter( 'woocommerce_products_admin_list_table_filters', [ $this, 'product_filter' ] );
					add_action( 'pre_get_posts', [ $this, 'apply_product_filter' ] );

					// HPOS compatibility
					add_action( 'before_woocommerce_init', function () {
						if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
							\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WOOBT_FILE, true );
						}
					} );
				}

				function init() {
					self::$types      = (array) apply_filters( 'woobt_product_types', self::$types );
					self::$image_size = apply_filters( 'woobt_image_size', self::$image_size );
				}

				function available_variation( $data, $variable, $variation ) {
					if ( $image_id = $variation->get_image_id() ) {
						$data['woobt_image'] = wp_get_attachment_image( $image_id, self::$image_size );
					}

					return $data;
				}

				public static function get_settings() {
					return apply_filters( 'woobt_get_settings', self::$settings );
				}

				public static function get_setting( $name, $default = false ) {
					if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
						$setting = self::$settings[ $name ];
					} else {
						$setting = get_option( '_woobt_' . $name, $default );
					}

					return apply_filters( 'woobt_get_setting', $setting, $name, $default );
				}

				function register_settings() {
					// settings
					register_setting( 'woobt_settings', 'woobt_settings' );

					// localization
					register_setting( 'woobt_localization', 'woobt_localization' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Frequently Bought Together', 'woo-bought-together' ), esc_html__( 'Bought Together', 'woo-bought-together' ), 'manage_options', 'wpclever-woobt', array(
						$this,
						'admin_menu_content'
					) );
				}

				function admin_menu_content() {
					add_thickbox();
					$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Frequently Bought Together', 'woo-bought-together' ) . ' ' . WOOBT_VERSION; ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( /* translators: %s is the stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'woo-bought-together' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WOOBT_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'woo-bought-together' ); ?></a> | <a
                                        href="<?php echo esc_url( WOOBT_CHANGELOG ); ?>"
                                        target="_blank"><?php esc_html_e( 'Changelog', 'woo-bought-together' ); ?></a>
                                | <a href="<?php echo esc_url( WOOBT_DISCUSSION ); ?>"
                                     target="_blank"><?php esc_html_e( 'Discussion', 'woo-bought-together' ); ?></a>
                            </p>
                        </div>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'woo-bought-together' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woobt&tab=how' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'how' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'How to use?', 'woo-bought-together' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woobt&tab=settings' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Settings', 'woo-bought-together' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woobt&tab=localization' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Localization', 'woo-bought-together' ); ?>
                                </a>
                                <!--
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-woobt&tab=premium' ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>" style="color: #c9356e">
									<?php esc_html_e( 'Premium Version', 'woo-bought-together' ); ?>
                                </a>
                                -->
                                <a href="<?php echo esc_url( WOOBT_SUPPORT ); ?>" class="nav-tab" target="_blank">
									<?php esc_html_e( 'Support', 'woo-bought-together' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=wpclever-kit' ); ?>" class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'woo-bought-together' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'how' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>
										<?php esc_html_e( 'When adding/editing the product you can choose Bought Together tab then add some products with the new price.', 'woo-bought-together' ); ?>
                                    </p>
                                    <p>
                                        <img src="<?php echo esc_url( WOOBT_URI ); ?>assets/images/how-01.jpg"/>
                                    </p>
                                </div>
							<?php } elseif ( $active_tab === 'settings' ) {
								$pricing               = self::get_setting( 'pricing', 'sale_price' );
								$default               = self::get_setting( 'default', 'none' );
								$layout                = self::get_setting( 'layout', 'default' );
								$atc_button            = self::get_setting( 'atc_button', 'main' );
								$show_this_item        = self::get_setting( 'show_this_item', 'yes' );
								$exclude_unpurchasable = self::get_setting( 'exclude_unpurchasable', 'no' );
								$show_thumb            = self::get_setting( 'show_thumb', 'yes' );
								$show_price            = self::get_setting( 'show_price', 'yes' );
								$show_description      = self::get_setting( 'show_description', 'no' );
								$plus_minus            = self::get_setting( 'plus_minus', 'no' );
								$variations_selector   = self::get_setting( 'variations_selector', 'default' );
								$link                  = self::get_setting( 'link', 'yes' );
								$change_image          = self::get_setting( 'change_image', 'yes' );
								$change_price          = self::get_setting( 'change_price', 'yes' );
								$counter               = self::get_setting( 'counter', 'individual' );
								$responsive            = self::get_setting( 'responsive', 'yes' );
								$cart_quantity         = self::get_setting( 'cart_quantity', 'yes' );
								?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'General', 'woo-bought-together' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Pricing method', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[pricing]">
                                                    <option value="sale_price" <?php selected( $pricing, 'sale_price' ); ?>><?php esc_html_e( 'from Sale price', 'woo-bought-together' ); ?></option>
                                                    <option value="regular_price" <?php selected( $pricing, 'regular_price' ); ?>><?php esc_html_e( 'from Regular price ', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Calculate prices from the sale price (default) or regular price of products.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Default products', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[default]" class="woobt_default">
                                                    <option value="upsells" <?php selected( $default, 'upsells' ); ?>><?php esc_html_e( 'Upsells', 'woo-bought-together' ); ?></option>
                                                    <option value="related" <?php selected( $default, 'related' ); ?>><?php esc_html_e( 'Related', 'woo-bought-together' ); ?></option>
                                                    <option value="related_upsells" <?php selected( $default, 'related_upsells' ); ?>><?php esc_html_e( 'Related & Upsells', 'woo-bought-together' ); ?></option>
                                                    <option value="none" <?php selected( $default, 'none' ); ?>><?php esc_html_e( 'None', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Default products when don\'t specified any products.', 'woo-bought-together' ); ?></span>
                                                <span class="woobt_show_if_default_products"><?php esc_html_e( 'Limit', 'woo-bought-together' ); ?>
                                                    <input type="number" class="small-text"
                                                           name="woobt_settings[default_limit]"
                                                           value="<?php echo esc_attr( self::get_setting( 'default_limit' ) ); ?>"/> <?php esc_html_e( 'products.', 'woo-bought-together' ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Layout', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[layout]">
                                                    <option value="default" <?php selected( $layout, 'default' ); ?>><?php esc_html_e( 'Default', 'woo-bought-together' ); ?></option>
                                                    <option value="separate" <?php selected( $layout, 'separate' ); ?>><?php esc_html_e( 'Separate images', 'woo-bought-together' ); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Position', 'woo-bought-together' ); ?></th>
                                            <td>
												<?php
												$position  = apply_filters( 'woobt_position', self::get_setting( 'position', apply_filters( 'woobt_default_position', 'before' ) ) );
												$positions = apply_filters( 'woobt_positions', array(
													'before'        => esc_html__( 'Above add to cart button', 'woo-bought-together' ),
													'after'         => esc_html__( 'Under add to cart button', 'woo-bought-together' ),
													'below_title'   => esc_html__( 'Under the title', 'woo-bought-together' ),
													'below_price'   => esc_html__( 'Under the price', 'woo-bought-together' ),
													'below_excerpt' => esc_html__( 'Under the excerpt', 'woo-bought-together' ),
													'below_meta'    => esc_html__( 'Under the meta', 'woo-bought-together' ),
													'below_summary' => esc_html__( 'Under summary', 'woo-bought-together' ),
													'none'          => esc_html__( 'None (hide it)', 'woo-bought-together' ),
												) );

												if ( is_array( $positions ) && ( count( $positions ) > 0 ) ) {
													echo '<select name="woobt_settings[position]">';

													foreach ( $positions as $k => $p ) {
														echo '<option value="' . esc_attr( $k ) . '" ' . ( $k === $position ? 'selected' : '' ) . '>' . esc_html( $p ) . '</option>';
													}

													echo '</select>';
												}
												?>
                                                <span class="description"><?php esc_html_e( 'Choose the position to show the products list. You also can use the shortcode [woobt] to show the list where you want.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Add to cart button', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[atc_button]" class="woobt_atc_button">
                                                    <option value="main" <?php selected( $atc_button, 'main' ); ?>><?php esc_html_e( 'Main product\'s button', 'woo-bought-together' ); ?></option>
                                                    <option value="separate" <?php selected( $atc_button, 'separate' ); ?>><?php esc_html_e( 'Separate buttons', 'woo-bought-together' ); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show "this item"', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[show_this_item]"
                                                        class="woobt_show_this_item">
                                                    <option value="yes" <?php selected( $show_this_item, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $show_this_item, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( '"This item" cannot be hidden if "Separate buttons" is in use for the Add to Cart button.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Exclude unpurchasable', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[exclude_unpurchasable]">
                                                    <option value="yes" <?php selected( $exclude_unpurchasable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $exclude_unpurchasable, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Exclude unpurchasable products from the list.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show thumbnail', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[show_thumb]">
                                                    <option value="yes" <?php selected( $show_thumb, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $show_thumb, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show price', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[show_price]">
                                                    <option value="yes" <?php selected( $show_price, 'yes' ); ?>><?php esc_html_e( 'Price', 'woo-bought-together' ); ?></option>
                                                    <option value="total" <?php selected( $show_price, 'total' ); ?>><?php esc_html_e( 'Total', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $show_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show short description', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[show_description]">
                                                    <option value="yes" <?php selected( $show_description, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $show_description, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Show plus/minus button', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[plus_minus]">
                                                    <option value="yes" <?php selected( $plus_minus, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $plus_minus, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Show the plus/minus button for the quantity input.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Variations selector', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[variations_selector]">
                                                    <option value="default" <?php selected( $variations_selector, 'default' ); ?>><?php esc_html_e( 'Default', 'woo-bought-together' ); ?></option>
                                                    <option value="woovr" <?php selected( $variations_selector, 'woovr' ); ?>><?php esc_html_e( 'Use WPC Variations Radio Buttons', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description">If you choose "Use WPC Variations Radio Buttons", please install <a
                                                            href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=wpc-variations-radio-buttons&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                            class="thickbox"
                                                            title="Install WPC Variations Radio Buttons">WPC Variations Radio Buttons</a> to make it work.</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Link to individual product', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[link]">
                                                    <option value="yes" <?php selected( $link, 'yes' ); ?>><?php esc_html_e( 'Yes, open in the same tab', 'woo-bought-together' ); ?></option>
                                                    <option value="yes_blank" <?php selected( $link, 'yes_blank' ); ?>><?php esc_html_e( 'Yes, open in the new tab', 'woo-bought-together' ); ?></option>
                                                    <option value="yes_popup" <?php selected( $link, 'yes_popup' ); ?>><?php esc_html_e( 'Yes, open quick view popup', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $link, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description">If you choose "Open quick view popup", please install <a
                                                            href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                            class="thickbox" title="Install WPC Smart Quick View">WPC Smart Quick View</a> to make it work.</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Change image', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[change_image]">
                                                    <option value="yes" <?php selected( $change_image, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $change_image, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Change the main product image when choosing the variation of variable products.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Change price', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[change_price]" class="woobt_change_price">
                                                    <option value="yes" <?php selected( $change_price, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="yes_custom" <?php selected( $change_price, 'yes_custom' ); ?>><?php esc_html_e( 'Yes, custom selector', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $change_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <input type="text" name="woobt_settings[change_price_custom]"
                                                       value="<?php echo self::get_setting( 'change_price_custom', '.summary > .price' ); ?>"
                                                       placeholder=".summary > .price"
                                                       class="woobt_change_price_custom"/>
                                                <span class="description"><?php esc_html_e( 'Change the main product price when choosing the variation or quantity of products. It uses JavaScript to change product price so it is very dependent on theme’s HTML. If it cannot find and update the product price, please contact us and we can help you find the right selector or adjust the JS file.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Counter', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[counter]">
                                                    <option value="individual" <?php selected( $counter, 'individual' ); ?>><?php esc_html_e( 'Count the individual products', 'woo-bought-together' ); ?></option>
                                                    <option value="qty" <?php selected( $counter, 'qty' ); ?>><?php esc_html_e( 'Count the product quantities', 'woo-bought-together' ); ?></option>
                                                    <option value="hide" <?php selected( $counter, 'hide' ); ?>><?php esc_html_e( 'Hide', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Counter on the add to cart button.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Responsive', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[responsive]">
                                                    <option value="yes" <?php selected( $responsive, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $responsive, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Change the layout for small screen devices.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'Cart & Checkout', 'woo-bought-together' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Change quantity', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <select name="woobt_settings[cart_quantity]">
                                                    <option value="yes" <?php selected( $cart_quantity, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                                    <option value="no" <?php selected( $cart_quantity, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                                                </select>
                                                <span class="description"><?php esc_html_e( 'Buyer can change the quantity of associated products or not?', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'Search', 'woo-bought-together' ); ?>
                                            </th>
                                        </tr>
										<?php self::search_settings(); ?>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'woobt_settings' ); ?>
												<?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab === 'localization' ) { ?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th scope="row"><?php esc_html_e( 'General', 'woo-bought-together' ); ?></th>
                                            <td>
												<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'woo-bought-together' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'This item', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[this_item]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'this_item' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'This item:', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Choose an attribute', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[choose]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'choose' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Choose %s', 'woo-bought-together' ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'Use %s to show the attribute name.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Clear', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[clear]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'clear' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Clear', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Additional price', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[additional]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'additional' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Additional price:', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Total price', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[total]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'total' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Total:', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Associated', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[associated]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'associated' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( '(bought together %s)', 'woo-bought-together' ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'The text behind associated products. Use "%s" for the main product name.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Add to cart', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[add_to_cart]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'add_to_cart' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Add to cart', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Add all to cart', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[add_all_to_cart]"
                                                       class="regular-text"
                                                       value="<?php echo esc_attr( self::localization( 'add_all_to_cart' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Add all to cart', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Default above text', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[above_text]"
                                                       class="large-text"
                                                       value="<?php echo esc_attr( self::localization( 'above_text' ) ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'The default text above products list. You can overwrite it for each product in product settings.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Default under text', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[under_text]"
                                                       class="large-text"
                                                       value="<?php echo esc_attr( self::localization( 'under_text' ) ); ?>"/>
                                                <span class="description"><?php esc_html_e( 'The default text under products list. You can overwrite it for each product in product settings.', 'woo-bought-together' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'Alert', 'woo-bought-together' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Require selection', 'woo-bought-together' ); ?></th>
                                            <td>
                                                <input type="text" name="woobt_localization[alert_selection]"
                                                       class="large-text"
                                                       value="<?php echo esc_attr( self::localization( 'alert_selection' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Please select a purchasable variation for [name] before adding this product to the cart.', 'woo-bought-together' ); ?>"/>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'woobt_localization' ); ?>
												<?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab == 'premium' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>Get the Premium Version just $29! <a
                                                href="https://wpclever.net/downloads/frequently-bought-together?utm_source=pro&utm_medium=woobt&utm_campaign=wporg"
                                                target="_blank">https://wpclever.net/downloads/frequently-bought-together</a>
                                    </p>
                                    <p><strong>Extra features for Premium Version:</strong></p>
                                    <ul style="margin-bottom: 0">
                                        <li>- Add a variable product or a specific variation of a product.</li>
                                        <li>- Get the lifetime update & premium support.</li>
                                    </ul>
                                </div>
							<?php } ?>
                        </div>
                    </div>
					<?php
				}

				function search_settings() {
					$search_sku      = self::get_setting( 'search_sku', 'no' );
					$search_id       = self::get_setting( 'search_id', 'no' );
					$search_exact    = self::get_setting( 'search_exact', 'no' );
					$search_sentence = self::get_setting( 'search_sentence', 'no' );
					$search_same     = self::get_setting( 'search_same', 'no' );
					?>
                    <tr>
                        <th><?php esc_html_e( 'Search limit', 'woo-bought-together' ); ?></th>
                        <td>
                            <input class="woobt_search_limit" type="number" min="1" max="500"
                                   name="woobt_settings[search_limit]"
                                   value="<?php echo self::get_setting( 'search_limit', 10 ); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Search by SKU', 'woo-bought-together' ); ?></th>
                        <td>
                            <select name="woobt_settings[search_sku]" class="woobt_search_sku">
                                <option value="yes" <?php selected( $search_sku, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                <option value="no" <?php selected( $search_sku, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Search by ID', 'woo-bought-together' ); ?></th>
                        <td>
                            <select name="woobt_settings[search_id]" class="woobt_search_id">
                                <option value="yes" <?php selected( $search_id, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                <option value="no" <?php selected( $search_id, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'Search by ID when entering the numeric only.', 'woo-bought-together' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Search exact', 'woo-bought-together' ); ?></th>
                        <td>
                            <select name="woobt_settings[search_exact]" class="woobt_search_exact">
                                <option value="yes" <?php selected( $search_exact, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                <option value="no" <?php selected( $search_exact, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'Match whole product title or content?', 'woo-bought-together' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Search sentence', 'woo-bought-together' ); ?></th>
                        <td>
                            <select name="woobt_settings[search_sentence]" class="woobt_search_sentence">
                                <option value="yes" <?php selected( $search_sentence, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                <option value="no" <?php selected( $search_sentence, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'Do a phrase search?', 'woo-bought-together' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Accept same products', 'woo-bought-together' ); ?></th>
                        <td>
                            <select name="woobt_settings[search_same]" class="woobt_search_same">
                                <option value="yes" <?php selected( $search_same, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-bought-together' ); ?></option>
                                <option value="no" <?php selected( $search_same, 'no' ); ?>><?php esc_html_e( 'No', 'woo-bought-together' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'If yes, a product can be added many times.', 'woo-bought-together' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Product types', 'woo-bought-together' ); ?></th>
                        <td>
							<?php
							$search_types  = self::get_setting( 'search_types', array( 'all' ) );
							$product_types = wc_get_product_types();
							$product_types = array_merge( array( 'all' => esc_html__( 'All', 'woo-bought-together' ) ), $product_types );
							$key_pos       = array_search( 'variable', array_keys( $product_types ) );

							if ( $key_pos !== false ) {
								$key_pos ++;
								$second_array  = array_splice( $product_types, $key_pos );
								$product_types = array_merge( $product_types, array( 'variation' => esc_html__( ' → Variation', 'woo-bought-together' ) ), $second_array );
							}

							echo '<select name="woobt_settings[search_types][]" multiple style="width: 200px; height: 150px;" class="woobt_search_types">';

							foreach ( $product_types as $key => $name ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . ( in_array( $key, $search_types, true ) ? 'selected' : '' ) . '>' . esc_html( $name ) . '</option>';
							}

							echo '</select>';
							?>
                        </td>
                    </tr>
					<?php
				}

				function enqueue_scripts() {
					wp_enqueue_style( 'woobt-frontend', WOOBT_URI . 'assets/css/frontend.css', array(), WOOBT_VERSION );
					wp_enqueue_script( 'woobt-frontend', WOOBT_URI . 'assets/js/frontend.js', array( 'jquery' ), WOOBT_VERSION, true );
					wp_localize_script( 'woobt-frontend', 'woobt_vars', array(
							'ajax_url'                 => admin_url( 'admin-ajax.php' ),
							'add_to_cart_button'       => self::get_setting( 'atc_button', 'main' ),
							'position'                 => apply_filters( 'woobt_position', self::get_setting( 'position', apply_filters( 'woobt_default_position', 'before' ) ) ),
							'change_image'             => self::get_setting( 'change_image', 'yes' ),
							'change_price'             => self::get_setting( 'change_price', 'yes' ),
							'price_selector'           => self::get_setting( 'change_price_custom', '' ),
							'this_item'                => self::get_setting( 'show_this_item', 'yes' ),
							'counter'                  => self::get_setting( 'counter', 'individual' ),
							'variation_selector'       => ( class_exists( 'WPClever_Woovr' ) && ( self::get_setting( 'variations_selector', 'default' ) === 'wpc_radio' || self::get_setting( 'variations_selector', 'default' ) === 'woovr' ) ) ? 'woovr' : 'default',
							'price_format'             => get_woocommerce_price_format(),
							'price_suffix'             => ( $suffix = get_option( 'woocommerce_price_display_suffix' ) ) && wc_tax_enabled() ? $suffix : '',
							'price_decimals'           => wc_get_price_decimals(),
							'price_thousand_separator' => wc_get_price_thousand_separator(),
							'price_decimal_separator'  => wc_get_price_decimal_separator(),
							'currency_symbol'          => get_woocommerce_currency_symbol(),
							'trim_zeros'               => apply_filters( 'woocommerce_price_trim_zeros', false ),
							'additional_price_text'    => self::localization( 'additional', esc_html__( 'Additional price:', 'woo-bought-together' ) ),
							'total_price_text'         => self::localization( 'total', esc_html__( 'Total:', 'woo-bought-together' ) ),
							'add_to_cart'              => self::get_setting( 'atc_button', 'main' ) === 'main' ? self::localization( 'add_to_cart', esc_html__( 'Add to cart', 'woo-bought-together' ) ) : self::localization( 'add_all_to_cart', esc_html__( 'Add all to cart', 'woo-bought-together' ) ),
							'alert_selection'          => self::localization( 'alert_selection', esc_html__( 'Please select a purchasable variation for [name] before adding this product to the cart.', 'woo-bought-together' ) ),
						)
					);
				}

				function admin_enqueue_scripts() {
					wp_enqueue_style( 'hint', WOOBT_URI . 'assets/css/hint.css' );
					wp_enqueue_style( 'woobt-backend', WOOBT_URI . 'assets/css/backend.css', array(), WOOBT_VERSION );
					wp_enqueue_script( 'woobt-backend', WOOBT_URI . 'assets/js/backend.js', array(
						'jquery',
						'jquery-ui-dialog',
						'jquery-ui-sortable'
					), WOOBT_VERSION, true );
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings = '<a href="' . admin_url( 'admin.php?page=wpclever-woobt&tab=settings' ) . '">' . esc_html__( 'Settings', 'woo-bought-together' ) . '</a>';
						//$links['wpc-premium']       = '<a href="' . admin_url( 'admin.php?page=wpclever-woobt&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'woo-bought-together' ) . '</a>';
						array_unshift( $links, $settings );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = array(
							'support' => '<a href="' . esc_url( WOOBT_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'woo-bought-together' ) . '</a>',
						);

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function display_post_states( $states, $post ) {
					if ( 'product' == get_post_type( $post->ID ) ) {
						if ( $ids = self::get_ids( $post->ID, 'edit' ) ) {
							$items = self::get_items( $ids, $post->ID, 'edit' );

							if ( ! empty( $items ) ) {
								$count    = count( $items );
								$states[] = apply_filters( 'woobt_post_states', '<span class="woobt-state">' . sprintf( /* translators: %s is the count */ esc_html__( 'Bought together (%s)', 'woo-bought-together' ), $count ) . '</span>', $count, $post->ID );
							}
						}
					}

					return $states;
				}

				function cart_item_removed( $cart_item_key, $cart ) {
					if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['woobt_keys'] ) ) {
						$keys = $cart->removed_cart_contents[ $cart_item_key ]['woobt_keys'];

						foreach ( $keys as $key ) {
							unset( $cart->cart_contents[ $key ] );
						}
					}
				}

				function cart_item_name( $item_name, $item ) {
					if ( isset( $item['woobt_parent_id'] ) && ! empty( $item['woobt_parent_id'] ) ) {
						$associated_text = self::localization( 'associated', esc_html__( '(bought together %s)', 'woo-bought-together' ) );
						$parent_id       = apply_filters( 'woobt_item_id', $item['woobt_parent_id'] );

						if ( strpos( $item_name, '</a>' ) !== false ) {
							$name = sprintf( $associated_text, '<a href="' . get_permalink( $parent_id ) . '">' . get_the_title( $parent_id ) . '</a>' );
						} else {
							$name = sprintf( $associated_text, get_the_title( $parent_id ) );
						}

						$item_name .= ' <span class="woobt-item-name">' . apply_filters( 'woobt_item_name', $name, $item ) . '</span>';
					}

					return $item_name;
				}

				function cart_item_price( $price, $cart_item ) {
					if ( isset( $cart_item['woobt_parent_id'], $cart_item['woobt_price'], $cart_item['woobt_price_item'] ) && ( $cart_item['woobt_price_item'] !== '100%' ) && ( $cart_item['woobt_price_item'] !== '' ) ) {
						return wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $cart_item['woobt_price'] ) ) );
					}

					return $price;
				}

				function cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
					// add qty as text - not input
					if ( isset( $cart_item['woobt_parent_id'] ) ) {
						if ( ( self::get_setting( 'cart_quantity', 'yes' ) === 'no' ) || ( isset( $cart_item['woobt_sync_qty'] ) && $cart_item['woobt_sync_qty'] ) ) {
							return $cart_item['quantity'];
						}
					}

					return $quantity;
				}

				function check_in_cart( $product_id ) {
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( $cart_item['product_id'] === $product_id ) {
							return true;
						}
					}

					return false;
				}

				function found_in_cart( $found_in_cart, $product_id ) {
					if ( apply_filters( 'woobt_sold_individually_found_in_cart', true ) && self::check_in_cart( $product_id ) ) {
						return true;
					}

					return $found_in_cart;
				}

				function add_to_cart_validation( $passed, $product_id ) {
					if ( ( get_post_meta( $product_id, 'woobt_separately', true ) !== 'on' ) && self::get_ids( $product_id, 'validate' ) ) {
						if ( isset( $_REQUEST['woobt_ids'] ) || isset( $_REQUEST['data']['woobt_ids'] ) ) {
							if ( isset( $_REQUEST['woobt_ids'] ) ) {
								$items = self::get_items( $_REQUEST['woobt_ids'], $product_id );
							} elseif ( isset( $_REQUEST['data']['woobt_ids'] ) ) {
								$items = self::get_items( $_REQUEST['data']['woobt_ids'], $product_id );
							}

							if ( ! empty( $items ) ) {
								foreach ( $items as $item ) {
									$item_product = wc_get_product( $item['id'] );

									if ( ! $item_product ) {
										wc_add_notice( esc_html__( 'One of the associated products is unavailable.', 'woo-bought-together' ), 'error' );
										wc_add_notice( esc_html__( 'You cannot add this product to the cart.', 'woo-bought-together' ), 'error' );

										return false;
									}

									if ( $item_product->is_type( 'variable' ) ) {
										wc_add_notice( sprintf( /* translators: %s is the product name */ esc_html__( '"%s" is un-purchasable.', 'woo-bought-together' ), esc_html( apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product ) ) ), 'error' );
										wc_add_notice( esc_html__( 'You cannot add this product to the cart.', 'woo-bought-together' ), 'error' );

										return false;
									}

									if ( $item_product->is_sold_individually() && apply_filters( 'woobt_sold_individually_found_in_cart', true ) && self::check_in_cart( $item['id'] ) ) {
										wc_add_notice( sprintf( /* translators: %s is the product name */ esc_html__( 'You cannot add another "%s" to the cart.', 'woo-bought-together' ), esc_html( apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product ) ) ), 'error' );
										wc_add_notice( esc_html__( 'You cannot add this product to the cart.', 'woo-bought-together' ), 'error' );

										return false;
									}

									if ( apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id ) ) {
										if ( ( $limit_min = get_post_meta( $product_id, 'woobt_limit_each_min', true ) ) && ( $item['qty'] < (float) $limit_min ) ) {
											wc_add_notice( sprintf( /* translators: %s is the product name */ esc_html__( '"%s" does not reach the minimum quantity.', 'woo-bought-together' ), esc_html( apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product ) ) ), 'error' );
											wc_add_notice( esc_html__( 'You cannot add this product to the cart.', 'woo-bought-together' ), 'error' );

											return false;
										}

										if ( ( $limit_max = get_post_meta( $product_id, 'woobt_limit_each_max', true ) ) && ( $item['qty'] > (float) $limit_max ) ) {
											wc_add_notice( sprintf( /* translators: %s is the product name */ esc_html__( '"%s" passes the maximum quantity.', 'woo-bought-together' ), esc_html( apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product ) ) ), 'error' );
											wc_add_notice( esc_html__( 'You cannot add this product to the cart.', 'woo-bought-together' ), 'error' );

											return false;
										}
									}
								}
							}
						}
					}

					return $passed;
				}

				function add_cart_item_data( $cart_item_data, $product_id ) {
					if ( ( isset( $_REQUEST['woobt_ids'] ) || isset( $_REQUEST['data']['woobt_ids'] ) ) && ( get_post_meta( $product_id, 'woobt_separately', true ) !== 'on' ) && ( self::get_ids( $product_id, 'validate' ) || ( self::get_setting( 'default', 'none' ) !== 'none' ) ) ) {
						// make sure that is bought together product
						if ( isset( $_REQUEST['woobt_ids'] ) ) {
							$ids = self::clean_ids( $_REQUEST['woobt_ids'] );
						} elseif ( isset( $_REQUEST['data']['woobt_ids'] ) ) {
							$ids = self::clean_ids( $_REQUEST['data']['woobt_ids'] );
						}

						if ( ! empty( $ids ) ) {
							$cart_item_data['woobt_ids'] = $ids;
						}
					}

					return $cart_item_data;
				}

				function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
					if ( isset( $cart_item_data['_bundle_variation_id'] ) ) {
						// WC Product Bundles Variation
						$variation_product = wc_get_product( $cart_item_data['_bundle_variation_id'] );
						$product_id        = $variation_product->get_parent_id();
					}

					if ( ( isset( $_REQUEST['woobt_ids'] ) || isset( $_REQUEST['data']['woobt_ids'] ) ) && ( self::get_ids( $product_id, 'validate' ) || ( self::get_setting( 'default', 'none' ) !== 'none' ) ) ) {
						$ids = '';

						if ( isset( $_REQUEST['woobt_ids'] ) ) {
							$ids = self::clean_ids( $_REQUEST['woobt_ids'] );
							unset( $_REQUEST['woobt_ids'] );
						} elseif ( isset( $_REQUEST['data']['woobt_ids'] ) ) {
							$ids = self::clean_ids( $_REQUEST['data']['woobt_ids'] );
							unset( $_REQUEST['data']['woobt_ids'] );
						}

						if ( $items = self::get_items( $ids, $product_id ) ) {
							$custom_qty = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
							$sync_qty   = ! $custom_qty && apply_filters( 'woobt_sync_qty', get_post_meta( $product_id, 'woobt_sync_qty', true ) === 'on' );

							// add sync_qty for the main product
							if ( get_post_meta( $product_id, 'woobt_separately', true ) !== 'on' ) {
								WC()->cart->cart_contents[ $cart_item_key ]['woobt_ids']      = $ids;
								WC()->cart->cart_contents[ $cart_item_key ]['woobt_key']      = $cart_item_key;
								WC()->cart->cart_contents[ $cart_item_key ]['woobt_sync_qty'] = $sync_qty;
							}

							// add child products
							self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
						}
					}
				}

				function add_to_cart_items( $items, $cart_item_key, $product_id, $quantity ) {
					$pricing    = self::get_setting( 'pricing', 'sale_price' );
					$custom_qty = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
					$sync_qty   = ! $custom_qty && apply_filters( 'woobt_sync_qty', get_post_meta( $product_id, 'woobt_sync_qty', true ) === 'on' );

					// add child products
					foreach ( $items as $item ) {
						$item_id           = $item['id'];
						$item_price        = apply_filters( 'woobt_item_price', $item['price'], $item_id, $product_id );
						$item_qty          = $item['qty'];
						$item_variation    = $item['attrs'];
						$item_variation_id = 0;
						$item_product      = wc_get_product( $item_id );

						if ( $item_product instanceof WC_Product_Variation ) {
							// ensure we don't add a variation to the cart directly by variation ID
							$item_variation_id = $item_id;
							$item_id           = $item_product->get_parent_id();

							if ( empty( $item_variation ) ) {
								$item_variation = $item_product->get_variation_attributes();
							}
						}

						if ( $item_product && $item_product->is_in_stock() && $item_product->is_purchasable() && ( 'trash' !== $item_product->get_status() ) ) {
							if ( get_post_meta( $product_id, 'woobt_separately', true ) !== 'on' ) {
								// calc new price
								if ( $pricing === 'sale_price' ) {
									// from sale price
									$item_new_price = self::new_price( $item_product->get_price(), $item_price );
								} else {
									// from regular price
									$item_new_price = self::new_price( $item_product->get_regular_price(), $item_price );
								}

								// add to cart
								$item_key = WC()->cart->add_to_cart( $item_id, $item_qty, $item_variation_id, $item_variation, array(
									'woobt_parent_id'  => $product_id,
									'woobt_parent_key' => $cart_item_key,
									'woobt_qty'        => $item_qty,
									'woobt_sync_qty'   => $sync_qty,
									'woobt_price_item' => $item_price,
									'woobt_price'      => $item_new_price
								) );

								if ( $item_key ) {
									WC()->cart->cart_contents[ $item_key ]['woobt_key']         = $item_key;
									WC()->cart->cart_contents[ $cart_item_key ]['woobt_keys'][] = $item_key;
								}
							} else {
								if ( $sync_qty ) {
									WC()->cart->add_to_cart( $item_id, $item_qty * $quantity, $item_variation_id, $item_variation );
								} else {
									WC()->cart->add_to_cart( $item_id, $item_qty, $item_variation_id, $item_variation );
								}
							}
						}
					}
				}

				function add_all_to_cart() {
					ob_start();

					if ( ! isset( $_POST['product_id'] ) ) {
						return;
					}

					$product_id     = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
					$product        = wc_get_product( $product_id );
					$quantity       = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );
					$product_status = get_post_status( $product_id );
					$variation_id   = $_POST['variation_id'];
					$variation      = $_POST['variation'];

					if ( $product && 'variation' === $product->get_type() ) {
						$variation_id = $product_id;
						$product_id   = $product->get_parent_id();

						if ( empty( $variation ) ) {
							$variation = $product->get_variation_attributes();
						}
					}

					$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

					if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) && 'publish' === $product_status ) {
						do_action( 'woocommerce_ajax_added_to_cart', $product_id );

						if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
							wc_add_to_cart_message( array( $product_id => $quantity ), true );
						}

						WC_AJAX::get_refreshed_fragments();
					} else {
						$data = array(
							'error'       => true,
							'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
						);

						wp_send_json( $data );
					}

					die();
				}

				function before_mini_cart_contents() {
					WC()->cart->calculate_totals();
				}

				function before_calculate_totals( $cart_object ) {
					if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
						// This is necessary for WC 3.0+
						return;
					}

					$cart_contents = $cart_object->cart_contents;
					$new_keys      = [];

					foreach ( $cart_contents as $cart_item_key => $cart_item ) {
						if ( ! empty( $cart_item['woobt_key'] ) ) {
							$new_keys[ $cart_item_key ] = $cart_item['woobt_key'];
						}
					}

					foreach ( $cart_contents as $cart_item_key => $cart_item ) {
						// associated products
						if ( isset( $cart_item['woobt_parent_id'], $cart_item['woobt_price'], $cart_item['woobt_price_item'] ) && ( $cart_item['woobt_price_item'] !== '100%' ) && ( $cart_item['woobt_price_item'] !== '' ) ) {
							$cart_item['data']->set_price( $cart_item['woobt_price'] );
						}

						// sync quantity
						if ( ! empty( $cart_item['woobt_parent_key'] ) && ! empty( $cart_item['woobt_qty'] ) && ! empty( $cart_item['woobt_sync_qty'] ) ) {
							$parent_key     = $cart_item['woobt_parent_key'];
							$parent_new_key = array_search( $parent_key, $new_keys );

							if ( isset( $cart_contents[ $parent_key ] ) ) {
								WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['woobt_qty'] * $cart_contents[ $parent_key ]['quantity'];
							} elseif ( isset( $cart_contents[ $parent_new_key ] ) ) {
								WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['woobt_qty'] * $cart_contents[ $parent_new_key ]['quantity'];
							}
						}

						// main product
						if ( ! empty( $cart_item['woobt_ids'] ) && ( $discount = get_post_meta( $cart_item['product_id'], 'woobt_discount', true ) ) && ( get_post_meta( $cart_item['product_id'], 'woobt_separately', true ) !== 'on' ) ) {
							if ( $cart_item['variation_id'] > 0 ) {
								$item_product = wc_get_product( $cart_item['variation_id'] );
							} else {
								$item_product = wc_get_product( $cart_item['product_id'] );
							}

							$ori_price = $item_product->get_price();

							// has associated products
							$has_associated = false;

							if ( isset( $cart_item['woobt_keys'] ) ) {
								foreach ( $cart_item['woobt_keys'] as $key ) {
									if ( isset( $cart_contents[ $key ] ) ) {
										$has_associated = true;
										break;
									}
								}
							}

							if ( $has_associated && ! empty( $discount ) ) {
								$discount_price = $ori_price * ( 100 - (float) $discount ) / 100;
								$cart_item['data']->set_price( $discount_price );
							}
						}
					}
				}

				function get_cart_item_from_session( $cart_item, $item_session_values ) {
					if ( isset( $item_session_values['woobt_ids'] ) && ! empty( $item_session_values['woobt_ids'] ) ) {
						$cart_item['woobt_ids']      = $item_session_values['woobt_ids'];
						$cart_item['woobt_sync_qty'] = $item_session_values['woobt_sync_qty'];
					}

					if ( isset( $item_session_values['woobt_parent_id'] ) ) {
						$cart_item['woobt_parent_id']  = $item_session_values['woobt_parent_id'];
						$cart_item['woobt_parent_key'] = $item_session_values['woobt_parent_key'];
						$cart_item['woobt_price']      = $item_session_values['woobt_price'];
						$cart_item['woobt_price_item'] = $item_session_values['woobt_price_item'];
						$cart_item['woobt_qty']        = $item_session_values['woobt_qty'];
						$cart_item['woobt_sync_qty']   = $item_session_values['woobt_sync_qty'];
					}

					return $cart_item;
				}

				function order_line_item( $item, $cart_item_key, $values ) {
					// add _ to hide
					if ( isset( $values['woobt_parent_id'] ) ) {
						$item->update_meta_data( '_woobt_parent_id', $values['woobt_parent_id'] );
					}

					if ( isset( $values['woobt_ids'] ) ) {
						$item->update_meta_data( '_woobt_ids', $values['woobt_ids'] );
					}
				}

				function hidden_order_item_meta( $hidden ) {
					return array_merge( $hidden, array(
						'_woobt_parent_id',
						'_woobt_ids',
						'woobt_parent_id',
						'woobt_ids'
					) );
				}

				function before_order_item_meta( $item_id, $item ) {
					if ( $parent_id = $item->get_meta( '_woobt_parent_id' ) ) {
						echo sprintf( self::localization( 'associated', esc_html__( '(bought together %s)', 'woo-bought-together' ) ), get_the_title( $parent_id ) );
					}
				}

				function order_again_item_data( $data, $item ) {
					if ( $ids = $item->get_meta( '_woobt_ids' ) ) {
						$data['woobt_order_again'] = 'yes';
						$data['woobt_ids']         = $ids;
					}

					if ( $parent_id = $item->get_meta( '_woobt_parent_id' ) ) {
						$data['woobt_order_again'] = 'yes';
						$data['woobt_parent_id']   = $parent_id;
					}

					return $data;
				}

				function cart_loaded_from_session( $cart ) {
					foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
						// remove associated products first
						if ( isset( $cart_item['woobt_order_again'], $cart_item['woobt_parent_id'] ) ) {
							$cart->remove_cart_item( $cart_item_key );
						}
					}

					foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
						// add associated products again
						if ( isset( $cart_item['woobt_order_again'], $cart_item['woobt_ids'] ) ) {
							unset( $cart->cart_contents[ $cart_item_key ]['woobt_order_again'] );

							$product_id = $cart_item['product_id'];
							$custom_qty = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
							$sync_qty   = ! $custom_qty && apply_filters( 'woobt_sync_qty', get_post_meta( $product_id, 'woobt_sync_qty', true ) === 'on' );

							$cart->cart_contents[ $cart_item_key ]['woobt_key']      = $cart_item_key;
							$cart->cart_contents[ $cart_item_key ]['woobt_sync_qty'] = $sync_qty;

							if ( $items = self::get_items( $cart_item['woobt_ids'], $cart_item['product_id'] ) ) {
								self::add_to_cart_items( $items, $cart_item_key, $cart_item['product_id'], $cart_item['quantity'] );
							}
						}
					}
				}

				function cart_item_restored( $cart_item_key, $cart ) {
					if ( isset( $cart->cart_contents[ $cart_item_key ]['woobt_ids'] ) ) {
						// remove old keys
						unset( $cart->cart_contents[ $cart_item_key ]['woobt_keys'] );

						$ids        = $cart->cart_contents[ $cart_item_key ]['woobt_ids'];
						$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
						$quantity   = $cart->cart_contents[ $cart_item_key ]['quantity'];

						if ( get_post_meta( $product_id, 'woobt_separately', true ) !== 'on' ) {
							if ( $items = self::get_items( $ids, $product_id ) ) {
								self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
							}
						}
					}
				}

				function update_search_settings() {
					$settings = (array) get_option( 'woobt_settings', [] );

					$settings['search_limit']    = (int) sanitize_text_field( $_POST['limit'] );
					$settings['search_sku']      = sanitize_text_field( $_POST['sku'] );
					$settings['search_id']       = sanitize_text_field( $_POST['id'] );
					$settings['search_exact']    = sanitize_text_field( $_POST['exact'] );
					$settings['search_sentence'] = sanitize_text_field( $_POST['sentence'] );
					$settings['search_same']     = sanitize_text_field( $_POST['same'] );
					$settings['search_types']    = array_map( 'sanitize_text_field', (array) $_POST['types'] );

					update_option( 'woobt_settings', $settings );
					die();
				}

				function get_search_results() {
					$types          = self::get_setting( 'search_types', array( 'all' ) );
					$keyword        = esc_html( $_POST['woobt_keyword'] );
					$id             = absint( $_POST['woobt_id'] );
					$ids            = self::clean_ids( $_POST['woobt_ids'] );
					$exclude_ids    = array( $id );
					$added_products = explode( ',', $ids );

					if ( ( self::get_setting( 'search_id', 'no' ) === 'yes' ) && is_numeric( $keyword ) ) {
						// search by id
						$query_args = array(
							'p'         => absint( $keyword ),
							'post_type' => 'product'
						);
					} else {
						$query_args = array(
							'is_woobt'       => true,
							'post_type'      => 'product',
							'post_status'    => 'publish',
							's'              => $keyword,
							'posts_per_page' => self::get_setting( 'search_limit', 10 )
						);

						if ( ! empty( $types ) && ! in_array( 'all', $types, true ) ) {
							$product_types = $types;

							if ( in_array( 'variation', $types, true ) ) {
								$product_types[] = 'variable';
							}

							$query_args['tax_query'] = array(
								array(
									'taxonomy' => 'product_type',
									'field'    => 'slug',
									'terms'    => $product_types,
								),
							);
						}

						if ( self::get_setting( 'search_same', 'no' ) !== 'yes' ) {
							if ( is_array( $added_products ) && count( $added_products ) > 0 ) {
								foreach ( $added_products as $added_product ) {
									$added_product_data = explode( '/', $added_product );
									$exclude_ids[]      = absint( $added_product_data[0] ?: 0 );
								}
							}

							$query_args['post__not_in'] = $exclude_ids;
						}
					}

					$query = new WP_Query( $query_args );

					if ( $query->have_posts() ) {
						echo '<ul>';

						while ( $query->have_posts() ) {
							$query->the_post();
							$product = wc_get_product( get_the_ID() );

							if ( ! $product || ( 'trash' === $product->get_status() ) ) {
								continue;
							}

							if ( ! $product->is_type( 'variable' ) || in_array( 'variable', $types, true ) || in_array( 'all', $types, true ) ) {
								self::product_data_li( $product, '100%', 1, true );
							}

							if ( $product->is_type( 'variable' ) && ( empty( $types ) || in_array( 'all', $types, true ) || in_array( 'variation', $types, true ) ) ) {
								// show all children
								$children = $product->get_children();

								if ( is_array( $children ) && count( $children ) > 0 ) {
									foreach ( $children as $child ) {
										$product_child = wc_get_product( $child );

										if ( $product_child ) {
											self::product_data_li( $product_child, '100%', 1, true );
										}
									}
								}
							}
						}

						echo '</ul>';
						wp_reset_postdata();
					} else {
						echo '<ul><span>' . sprintf( /* translators: %s is the keyword */ esc_html__( 'No results found for "%s"', 'woo-bought-together' ), esc_html( $keyword ) ) . '</span></ul>';
					}

					die();
				}

				function product_data_li( $product, $price = '100%', $qty = 1, $search = false ) {
					$product_id    = $product->get_id();
					$product_class = 'woobt-item';
					$product_class .= ! $product->is_in_stock() ? ' out-of-stock' : '';
					$product_class .= ! in_array( $product->get_type(), self::$types, true ) ? ' disabled' : '';

					if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
						$step = '0.000001';
					} else {
						$step = '1';
						$qty  = (int) $qty;
					}

					if ( $search ) {
						$remove_btn = '<span class="woobt-remove hint--left" aria-label="' . esc_html__( 'Add', 'woo-bought-together' ) . '">+</span>';
					} else {
						$remove_btn = '<span class="woobt-remove hint--left" aria-label="' . esc_html__( 'Remove', 'woo-bought-together' ) . '">×</span>';
					}

					echo '<li class="' . esc_attr( trim( $product_class ) ) . '" data-id="' . $product->get_id() . '"><span class="woobt-move"></span><span class="price hint--right" aria-label="' . esc_html__( 'Set a new price using a number (eg. "49") or percentage (eg. "90%" of original price)', 'woo-bought-together' ) . '"><input type="text" value="' . $price . '"/></span><span class="qty hint--right" aria-label="' . esc_html__( 'Default quantity', 'woo-bought-together' ) . '"><input type="number" value="' . esc_attr( $qty ) . '" step="' . esc_attr( $step ) . '"/></span> <span class="data">' . ( $product->get_status() === 'private' ? '<span class="info">private</span> ' : '' ) . '<span class="name">' . strip_tags( $product->get_name() ) . '</span> <span class="info">' . $product->get_price_html() . '</span></span> <span class="type"><a href="' . get_edit_post_link( $product_id ) . '" target="_blank">' . $product->get_type() . '<br/>#' . $product->get_id() . '</a></span> ' . $remove_btn . '</li>';
				}

				function product_data_tabs( $tabs ) {
					$tabs['woobt'] = array(
						'label'  => esc_html__( 'Bought Together', 'woo-bought-together' ),
						'target' => 'woobt_settings',
					);

					return $tabs;
				}

				function product_data_panels() {
					global $post;
					$post_id = $post->ID;
					?>
                    <div id='woobt_settings' class='panel woocommerce_options_panel woobt_table'>
                        <div id="woobt_search_settings" style="display: none"
                             data-title="<?php esc_html_e( 'Search settings', 'woo-bought-together' ); ?>">
                            <table>
								<?php self::search_settings(); ?>
                                <tr>
                                    <th></th>
                                    <td>
                                        <button id="woobt_search_settings_update" class="button button-primary">
											<?php esc_html_e( 'Update Options', 'woo-bought-together' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <table>
                            <tr>
                                <th><?php esc_html_e( 'Search', 'woo-bought-together' ); ?> (<a
                                            href="<?php echo admin_url( 'admin.php?page=wpclever-woobt&tab=settings#search' ); ?>"
                                            id="woobt_search_settings_btn"><?php esc_html_e( 'settings', 'woo-bought-together' ); ?></a>)
                                </th>
                                <td>
                                    <div class="w100">
								<span class="loading"
                                      id="woobt_loading"
                                      style="display: none"><?php esc_html_e( 'searching...', 'woo-bought-together' ); ?></span>
                                        <input type="search" id="woobt_keyword"
                                               placeholder="<?php esc_attr_e( 'Type any keyword to search', 'woo-bought-together' ); ?>"/>
                                        <div id="woobt_results" class="woobt_results" style="display: none"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Selected', 'woo-bought-together' ); ?></th>
                                <td>
                                    <div class="w100">
                                        <input type="hidden" id="woobt_id" name="woobt_id"
                                               value="<?php echo esc_attr( $post_id ); ?>"/>
                                        <input type="hidden" id="woobt_ids" name="woobt_ids"
                                               value="<?php echo self::get_ids( $post_id, 'edit' ); ?>"
                                               readonly/>
                                        <div id="woobt_selected" class="woobt_selected">
                                            <ul>
												<?php
												echo '<li class="woobt_default">' . sprintf( esc_html__( '* If don\'t choose any products, it can shows the default products %s.', 'woo-bought-together' ), '<a
                                                    href="' . admin_url( 'admin.php?page=wpclever-woobt&tab=settings' ) . '" target="_blank">' . esc_html__( 'here', 'woo-bought-together' ) . '</a>' ) . '</li>';

												if ( $ids = self::get_ids( $post_id, 'edit' ) ) {
													if ( $items = self::get_items( $ids, $post_id, 'edit' ) ) {
														foreach ( $items as $item ) {
															$item_id      = $item['id'];
															$item_price   = $item['price'];
															$item_qty     = $item['qty'];
															$item_product = wc_get_product( $item_id );

															if ( ! $item_product ) {
																continue;
															}

															self::product_data_li( $item_product, $item_price, $item_qty, false );
														}
													}
												}
												?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Add separately', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_separately" name="woobt_separately"
                                           type="checkbox" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_separately', true ) === 'on' ? 'checked' : '' ); ?>/>
                                    <span class="woocommerce-help-tip"
                                          data-tip="<?php esc_attr_e( 'If enabled, the associated products will be added as separate items and stay unaffected from the main product, their prices will change back to the original.', 'woo-bought-together' ); ?>"></span>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Selecting method', 'woo-bought-together' ); ?></th>
                                <td>
                                    <select name="woobt_selection">
                                        <option value="multiple" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_selection', true ) === 'multiple' ? 'selected' : '' ); ?>><?php esc_html_e( 'Multiple selection (default)', 'woo-bought-together' ); ?></option>
                                        <option value="single" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_selection', true ) === 'single' ? 'selected' : '' ); ?>><?php esc_html_e( 'Single selection (choose 1 only)', 'woo-bought-together' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Discount', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_discount" name="woobt_discount"
                                           type="number" min="0" max="100" step="0.0001" style="width: 50px"
                                           value="<?php echo get_post_meta( $post_id, 'woobt_discount', true ); ?>"/>%
                                    <span class="woocommerce-help-tip"
                                          data-tip="Discount for the main product when buying at least one product in this list."></span>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Checked all', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_checked_all" name="woobt_checked_all"
                                           type="checkbox" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_checked_all', true ) === 'on' ? 'checked' : '' ); ?>/>
                                    <label for="woobt_checked_all"><?php esc_html_e( 'Checked all by default.', 'woo-bought-together' ); ?></label>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Custom quantity', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_custom_qty" name="woobt_custom_qty"
                                           type="checkbox" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_custom_qty', true ) === 'on' ? 'checked' : '' ); ?>/>
                                    <label for="woobt_custom_qty"><?php esc_html_e( 'Allow the customer can change the quantity of each product.', 'woo-bought-together' ); ?></label>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space woobt_tr_hide_if_custom_qty">
                                <th><?php esc_html_e( 'Sync quantity', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_sync_qty" name="woobt_sync_qty"
                                           type="checkbox" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_sync_qty', true ) === 'on' ? 'checked' : '' ); ?>/>
                                    <label for="woobt_sync_qty"><?php esc_html_e( 'Sync the quantity of the main product with associated products.', 'woo-bought-together' ); ?></label>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space woobt_tr_show_if_custom_qty">
                                <th><?php esc_html_e( 'Limit each item', 'woo-bought-together' ); ?></th>
                                <td>
                                    <input id="woobt_limit_each_min_default" name="woobt_limit_each_min_default"
                                           type="checkbox" <?php echo esc_attr( get_post_meta( $post_id, 'woobt_limit_each_min_default', true ) === 'on' ? 'checked' : '' ); ?>/>
                                    <label for="woobt_limit_each_min_default"><?php esc_html_e( 'Use default quantity as min', 'woo-bought-together' ); ?></label>
                                    <u>or</u> Min <input name="woobt_limit_each_min" type="number"
                                                         min="0"
                                                         value="<?php echo esc_attr( get_post_meta( $post_id, 'woobt_limit_each_min', true ) ?: '' ); ?>"
                                                         style="width: 60px; float: none"/> Max <input
                                            name="woobt_limit_each_max"
                                            type="number" min="1"
                                            value="<?php echo esc_attr( get_post_meta( $post_id, 'woobt_limit_each_max', true ) ?: '' ); ?>"
                                            style="width: 60px; float: none"/>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Above text', 'woo-bought-together' ); ?></th>
                                <td>
                                    <div class="w100">
                                        <textarea name="woobt_before_text" rows="1"
                                                  style="width: 100%"><?php echo stripslashes( get_post_meta( $post_id, 'woobt_before_text', true ) ); ?></textarea>
                                    </div>
                                </td>
                            </tr>
                            <tr class="woobt_tr_space">
                                <th><?php esc_html_e( 'Under text', 'woo-bought-together' ); ?></th>
                                <td>
                                    <div class="w100">
                                        <textarea name="woobt_after_text" rows="1"
                                                  style="width: 100%"><?php echo stripslashes( get_post_meta( $post_id, 'woobt_after_text', true ) ); ?></textarea>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
					<?php
				}

				function process_product_meta( $post_id ) {
					if ( isset( $_POST['woobt_ids'] ) ) {
						$ids = self::clean_ids( sanitize_text_field( $_POST['woobt_ids'] ) );
						update_post_meta( $post_id, 'woobt_ids', $ids );
					}

					if ( ! empty( $_POST['woobt_discount'] ) ) {
						update_post_meta( $post_id, 'woobt_discount', sanitize_text_field( $_POST['woobt_discount'] ) );
					} else {
						delete_post_meta( $post_id, 'woobt_discount' );
					}

					if ( ! empty( $_POST['woobt_before_text'] ) ) {
						update_post_meta( $post_id, 'woobt_before_text', addslashes( $_POST['woobt_before_text'] ) );
					} else {
						delete_post_meta( $post_id, 'woobt_before_text' );
					}

					if ( ! empty( $_POST['woobt_after_text'] ) ) {
						update_post_meta( $post_id, 'woobt_after_text', addslashes( $_POST['woobt_after_text'] ) );
					} else {
						delete_post_meta( $post_id, 'woobt_after_text' );
					}

					if ( isset( $_POST['woobt_checked_all'] ) ) {
						update_post_meta( $post_id, 'woobt_checked_all', 'on' );
					} else {
						update_post_meta( $post_id, 'woobt_checked_all', 'off' );
					}

					if ( isset( $_POST['woobt_separately'] ) ) {
						update_post_meta( $post_id, 'woobt_separately', 'on' );
					} else {
						update_post_meta( $post_id, 'woobt_separately', 'off' );
					}

					if ( isset( $_POST['woobt_selection'] ) ) {
						update_post_meta( $post_id, 'woobt_selection', sanitize_text_field( $_POST['woobt_selection'] ) );
					}

					if ( isset( $_POST['woobt_custom_qty'] ) ) {
						update_post_meta( $post_id, 'woobt_custom_qty', 'on' );
					} else {
						update_post_meta( $post_id, 'woobt_custom_qty', 'off' );
					}

					if ( isset( $_POST['woobt_sync_qty'] ) ) {
						update_post_meta( $post_id, 'woobt_sync_qty', 'on' );
					} else {
						update_post_meta( $post_id, 'woobt_sync_qty', 'off' );
					}

					if ( isset( $_POST['woobt_limit_each_min_default'] ) ) {
						update_post_meta( $post_id, 'woobt_limit_each_min_default', 'on' );
					} else {
						update_post_meta( $post_id, 'woobt_limit_each_min_default', 'off' );
					}

					if ( isset( $_POST['woobt_limit_each_min'] ) ) {
						update_post_meta( $post_id, 'woobt_limit_each_min', sanitize_text_field( $_POST['woobt_limit_each_min'] ) );
					}

					if ( isset( $_POST['woobt_limit_each_max'] ) ) {
						update_post_meta( $post_id, 'woobt_limit_each_max', sanitize_text_field( $_POST['woobt_limit_each_max'] ) );
					}
				}

				function product_price_class( $class ) {
					global $product;

					return $class . ' woobt-price-' . $product->get_id();
				}

				function add_to_cart_form( $custom_position = false ) {
					global $product;

					if ( ! $product || $product->is_type( 'grouped' ) ) {
						return;
					}

					self::show_items( null, $custom_position );
				}

				function add_to_cart_button() {
					global $product;

					if ( ! $product->is_type( 'grouped' ) ) {
						echo '<input name="woobt_ids" class="woobt-ids woobt-ids-' . esc_attr( $product->get_id() ) . '" data-id="' . esc_attr( $product->get_id() ) . '" type="hidden"/>';
					}
				}

				function has_variables( $items ) {
					foreach ( $items as $item ) {
						if ( is_array( $item ) && isset( $item['id'] ) ) {
							$item_id = $item['id'];
						} else {
							$item_id = absint( $item );
						}

						$item_product = wc_get_product( $item_id );

						if ( ! $item_product ) {
							continue;
						}

						if ( $item_product->is_type( 'variable' ) ) {
							return true;
						}
					}

					return false;
				}

				function shortcode( $attrs ) {
					$attrs = shortcode_atts( array( 'id' => null, 'custom_position' => true ), $attrs );

					ob_start();
					self::show_items( $attrs['id'], wc_string_to_bool( $attrs['custom_position'] ) );

					return ob_get_clean();
				}

				function show_items( $product_id = null, $is_custom_position = false ) {
					if ( ! $product_id ) {
						global $product;

						if ( $product ) {
							$product_id = $product->get_id();
						}
					} else {
						$product = wc_get_product( $product_id );
					}

					if ( ! $product_id || ! $product ) {
						return;
					}

					wp_enqueue_script( 'wc-add-to-cart-variation' );

					$items         = array();
					$pricing       = self::get_setting( 'pricing', 'sale_price' );
					$custom_qty    = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
					$sync_qty      = apply_filters( 'woobt_sync_qty', get_post_meta( $product_id, 'woobt_sync_qty', true ) === 'on', $product_id );
					$separately    = apply_filters( 'woobt_separately', get_post_meta( $product_id, 'woobt_separately', true ) === 'on', $product_id );
					$selection     = apply_filters( 'woobt_selection', get_post_meta( $product_id, 'woobt_selection', true ) ?: 'multiple', $product_id );
					$default_limit = (int) apply_filters( 'woobt_default_limit', self::get_setting( 'default_limit', 0 ) );

					if ( $ids = self::get_ids( $product_id ) ) {
						$items = self::get_items( $ids, $product_id );
					}

					if ( ! $items && ( self::get_setting( 'default', 'none' ) === 'upsells' ) ) {
						$items = $product->get_upsell_ids();

						if ( $default_limit ) {
							$items = array_slice( $items, 0, $default_limit );
						}
					}

					if ( ! $items && ( self::get_setting( 'default', 'none' ) === 'related' ) ) {
						$items = wc_get_related_products( $product_id );

						if ( $default_limit ) {
							$items = array_slice( $items, 0, $default_limit );
						}
					}

					if ( ! $items && ( self::get_setting( 'default', 'none' ) === 'related_upsells' ) ) {
						$items_upsells = $product->get_upsell_ids();
						$items_related = wc_get_related_products( $product_id );
						$items         = array_merge( $items_upsells, $items_related );

						if ( $default_limit ) {
							$items = array_slice( $items, 0, $default_limit );
						}
					}

					// filter items before showing
					$items = apply_filters( 'woobt_show_items', $items, $product_id );

					$layout             = self::get_setting( 'layout', 'default' );
					$is_separate_layout = $layout === 'separate';
					$is_separate_atc    = self::get_setting( 'atc_button', 'main' ) === 'separate';

					$wrap_class = 'woobt-wrap woobt-layout-' . esc_attr( $layout ) . ' woobt-wrap-' . esc_attr( $product_id ) . ' ' . ( self::get_setting( 'responsive', 'yes' ) === 'yes' ? 'woobt-wrap-responsive' : '' );

					if ( $is_custom_position ) {
						$wrap_class .= ' woobt-wrap-custom-position';
					}

					if ( $is_separate_atc ) {
						$wrap_class .= ' woobt-wrap-separate-atc';
					}

					if ( ! empty( $items ) ) {
						foreach ( $items as $key => $item ) {
							if ( is_array( $item ) ) {
								$_item['id']      = $item['id'];
								$_item['price']   = $item['price'];
								$_item['qty']     = $item['qty'];
								$_item['product'] = wc_get_product( $_item['id'] );
							} else {
								// make it works with upsells & related
								$_item['id']      = absint( $item );
								$_item['price']   = '100%';
								$_item['qty']     = 1;
								$_item['product'] = wc_get_product( $_item['id'] );
							}

							if ( ! $_item['product'] || ! in_array( $_item['product']->get_type(), self::$types, true ) || ( ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) && ( ! $_item['product']->is_purchasable() || ! $_item['product']->is_in_stock() ) ) ) {
								unset( $items[ $key ] );
								continue;
							}

							$items[ $key ] = $_item;
						}

						echo '<div class="' . esc_attr( $wrap_class ) . '" data-id="' . esc_attr( $product_id ) . '" data-selection="' . esc_attr( $selection ) . '">';

						do_action( 'woobt_wrap_before', $product );

						if ( $before_text = apply_filters( 'woobt_before_text', get_post_meta( $product_id, 'woobt_before_text', true ) ?: self::localization( 'above_text' ), $product_id ) ) {
							echo '<div class="woobt-before-text woobt-text">' . do_shortcode( stripslashes( $before_text ) ) . '</div>';
						}

						if ( $is_separate_layout ) {
							?>
                            <div class="woobt-images">
								<?php
								echo ' <div class="woobt-image woobt-image-this woobt-image-order-0 woobt-image-' . esc_attr( $product_id ) . '">' . $product->get_image( self::$image_size ) . '</div>';

								$order = 1;

								foreach ( $items as $item ) {
									$item_product = $item['product'];

									echo ' <div class="woobt-image woobt-image-order-' . $order . ' woobt-image-' . esc_attr( $item['id'] ) . '"><span>+</span>';

									if ( self::get_setting( 'link', 'yes' ) !== 'no' ) {
										echo '<a ' . ( self::get_setting( 'link', 'yes' ) === 'yes_popup' ? 'class="woosq-link" data-id="' . esc_attr( $item['id'] ) . '" data-context="woobt"' : '' ) . ' href="' . $item_product->get_permalink() . '" ' . ( self::get_setting( 'link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . $item_product->get_image( self::$image_size ) . '</a>';
									} else {
										echo $item_product->get_image( self::$image_size );
									}

									echo '</div>';
									$order ++;
								}
								?>
                            </div>
							<?php
						}

						$sku        = $product->get_sku();
						$weight     = htmlentities( wc_format_weight( $product->get_weight() ) );
						$dimensions = htmlentities( wc_format_dimensions( $product->get_dimensions( false ) ) );
						$price_html = htmlentities( $product->get_price_html() );
						?>
                        <div class="woobt-products woobt-products-<?php echo esc_attr( $product_id ); ?>"
                             data-show-price="<?php echo esc_attr( self::get_setting( 'show_price', 'yes' ) ); ?>"
                             data-optional="<?php echo esc_attr( $custom_qty ? 'on' : 'off' ); ?>"
                             data-sync-qty="<?php echo esc_attr( $sync_qty ? 'on' : 'off' ); ?>"
                             data-variables="<?php echo esc_attr( self::has_variables( $items ) ? 'yes' : 'no' ); ?>"
                             data-product-id="<?php echo esc_attr( $product->is_type( 'variable' ) ? '0' : $product_id ); ?>"
                             data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
                             data-product-price-suffix="<?php echo esc_attr( htmlentities( $product->get_price_suffix() ) ); ?>"
                             data-product-price-html="<?php echo esc_attr( $price_html ); ?>"
                             data-product-o_price-html="<?php echo esc_attr( $price_html ); ?>"
                             data-pricing="<?php echo esc_attr( $pricing ); ?>"
                             data-discount="<?php echo esc_attr( ! $separately && get_post_meta( $product_id, 'woobt_discount', true ) ? get_post_meta( $product_id, 'woobt_discount', true ) : '0' ); ?>"
                             data-product-sku="<?php echo esc_attr( $sku ); ?>"
                             data-product-o_sku="<?php echo esc_attr( $sku ); ?>"
                             data-product-weight="<?php echo esc_attr( $weight ); ?>"
                             data-product-o_weight="<?php echo esc_attr( $weight ); ?>"
                             data-product-dimensions="<?php echo esc_attr( $dimensions ); ?>"
                             data-product-o_dimensions="<?php echo esc_attr( $dimensions ); ?>">
							<?php
							// this item
							if ( $is_custom_position || $is_separate_atc || self::get_setting( 'show_this_item', 'yes' ) !== 'no' ) {
								echo self::product_this_output( $product, false, $is_custom_position );
							} else {
								echo self::product_this_output( $product, true, $is_custom_position );
							}

							// other items
							$order = 1;

							foreach ( $items as $item ) {
								echo self::product_output( $item, $product_id, $order );

								$order ++;
							} ?>
                        </div>
						<?php
						echo '<div class="woobt-additional woobt-text"></div>';
						echo '<div class="woobt-total woobt-text"></div>';
						echo '<div class="woobt-alert woobt-text"></div>';

						if ( $after_text = apply_filters( 'woobt_after_text', get_post_meta( $product_id, 'woobt_after_text', true ) ?: self::localization( 'under_text' ), $product_id ) ) {
							echo '<div class="woobt-after-text woobt-text">' . do_shortcode( stripslashes( $after_text ) ) . '</div>';
						}

						if ( $is_custom_position || $is_separate_atc ) {
							echo '<div class="woobt-actions">';
							echo '<div class="woobt-form">';
							echo '<input type="hidden" name="woobt_ids" class="woobt-ids woobt-ids-' . esc_attr( $product->get_id() ) . '" data-id="' . esc_attr( $product->get_id() ) . '"/>';
							echo '<input type="hidden" name="quantity" value="1"/>';
							echo '<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '">';
							echo '<input type="hidden" name="variation_id" class="variation_id" value="0">';
							echo '<button type="submit" class="single_add_to_cart_button button alt">' . self::localization( 'add_all_to_cart', esc_html__( 'Add all to cart', 'woo-bought-together' ) ) . '</button>';
							echo '</div>';
							echo '</div>';
						}

						do_action( 'woobt_wrap_after', $product );

						echo '</div>';
					}
				}

				function product_this_output( $product, $hide_this = false, $is_custom_position = false ) {
					$product_id         = $product->get_id();
					$product_name       = apply_filters( 'woobt_product_get_name', $product->get_name(), $product );
					$custom_qty         = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
					$separately         = apply_filters( 'woobt_separately', get_post_meta( $product_id, 'woobt_separately', true ) === 'on', $product_id );
					$layout             = self::get_setting( 'layout', 'default' );
					$is_separate_layout = $layout === 'separate';
					$is_separate_atc    = self::get_setting( 'atc_button', 'main' ) === 'separate';
					$plus_minus         = self::get_setting( 'plus_minus', 'no' ) === 'yes';

					ob_start();

					if ( $hide_this ) {
						?>
                        <div class="woobt-product woobt-product-this woobt-hide-this" data-order="0" data-qty="1"
                             data-id="<?php echo esc_attr( $product->is_type( 'variable' ) || ! $product->is_in_stock() ? 0 : $product_id ); ?>"
                             data-pid="<?php echo esc_attr( $product_id ); ?>"
                             data-name="<?php echo esc_attr( $product_name ); ?>"
                             data-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_price', wc_get_price_to_display( $product ), $product ) ); ?>"
                             data-regular-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_regular_price', wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), $product ) ); ?>">
                            <div class="woobt-choose">
                                <label for="woobt_checkbox_0"><?php echo esc_html( $product_name ); ?></label>
                                <input id="woobt_checkbox_0" class="woobt-checkbox woobt-checkbox-this" type="checkbox"
                                       checked disabled/>
                                <span class="checkmark"></span>
                            </div>
                        </div>
					<?php } else { ?>
                        <div class="woobt-product woobt-product-this" data-order="0" data-qty="1" data-o_qty="1"
                             data-id="<?php echo esc_attr( $product->is_type( 'variable' ) || ! $product->is_in_stock() ? 0 : $product_id ); ?>"
                             data-pid="<?php echo esc_attr( $product_id ); ?>"
                             data-name="<?php echo esc_attr( $product_name ); ?>"
                             data-new-price="<?php echo esc_attr( ! $separately && ( $discount = get_post_meta( $product_id, 'woobt_discount', true ) ) ? ( 100 - (float) $discount ) . '%' : '100%' ); ?>"
                             data-price-suffix="<?php echo esc_attr( htmlentities( $product->get_price_suffix() ) ); ?>"
                             data-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_price', wc_get_price_to_display( $product ), $product ) ); ?>"
                             data-regular-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_regular_price', wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), $product ) ); ?>">

							<?php do_action( 'woobt_product_before', $product ); ?>

                            <div class="woobt-choose">
                                <label for="woobt_checkbox_0"><?php echo esc_html( $product_name ); ?></label>
                                <input id="woobt_checkbox_0" class="woobt-checkbox woobt-checkbox-this" type="checkbox"
                                       checked disabled/>
                                <span class="checkmark"></span>
                            </div>

							<?php if ( self::get_setting( 'show_thumb', 'yes' ) !== 'no' ) { ?>
                                <div class="woobt-thumb">
                                    <div class="woobt-thumb-ori"><?php echo $product->get_image( self::$image_size ); ?></div>
                                    <div class="woobt-thumb-new"></div>
                                </div>
							<?php } ?>

                            <div class="woobt-title">
                                <span class="woobt-title-inner">
                                    <?php echo '<span>' . self::localization( 'this_item', esc_html__( 'This item:', 'woo-bought-together' ) ) . '</span> <span>' . apply_filters( 'woobt_product_get_name', $product->get_name(), $product ) . '</span>'; ?>
                                </span>

								<?php if ( $is_separate_layout && ( self::get_setting( 'show_price', 'yes' ) !== 'no' ) ) { ?>
                                    <span class="woobt-price">
                                        <span class="woobt-price-new">
                                            <?php
                                            if ( ! $separately && ( $discount = get_post_meta( $product_id, 'woobt_discount', true ) ) ) {
	                                            $sale_price = $product->get_price() * ( 100 - (float) $discount ) / 100;
	                                            echo wc_format_sale_price( $product->get_price(), $sale_price ) . $product->get_price_suffix( $sale_price );
                                            } else {
	                                            echo $product->get_price_html();
                                            }
                                            ?>
                                        </span>
                                        <span class="woobt-price-ori">
                                            <?php echo $product->get_price_html(); ?>
                                        </span>
                                    </span>
								<?php } ?>

								<?php
								if ( ( $is_separate_atc || $is_custom_position ) && $product->is_type( 'variable' ) ) {
									if ( ( self::get_setting( 'variations_selector', 'default' ) === 'wpc_radio' || self::get_setting( 'variations_selector', 'default' ) === 'woovr' ) && class_exists( 'WPClever_Woovr' ) ) {
										echo '<div class="wpc_variations_form">';
										// use class name wpc_variations_form to prevent found_variation in woovr
										WPClever_Woovr::woovr_variations_form( $product );
										echo '</div>';
									} else {
										$attributes           = $product->get_variation_attributes();
										$available_variations = $product->get_available_variations();

										if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {
											echo '<div class="variations_form" data-product_id="' . absint( $product_id ) . '" data-product_variations="' . htmlspecialchars( wp_json_encode( $available_variations ) ) . '">';
											echo '<div class="variations">';

											foreach ( $attributes as $attribute_name => $options ) { ?>
                                                <div class="variation">
                                                    <div class="label">
														<?php echo wc_attribute_label( $attribute_name ); ?>
                                                    </div>
                                                    <div class="select">
														<?php
														$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( stripslashes( urldecode( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ) ) : $product->get_variation_default_attribute( $attribute_name );
														wc_dropdown_variation_attribute_options( array(
															'options'          => $options,
															'attribute'        => $attribute_name,
															'product'          => $product,
															'selected'         => $selected,
															'show_option_none' => sprintf( self::localization( 'choose', esc_html__( 'Choose %s', 'woo-bought-together' ) ), wc_attribute_label( $attribute_name ) )
														) );
														?>
                                                    </div>
                                                </div>
											<?php }

											echo '<div class="reset">' . apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . self::localization( 'clear', esc_html__( 'Clear', 'woo-bought-together' ) ) . '</a>' ) . '</div>';
											echo '</div>';
											echo '</div>';

											if ( self::get_setting( 'show_description', 'no' ) === 'yes' ) {
												echo '<div class="woobt-variation-description"></div>';
											}
										}
									}
								}

								echo '<div class="woobt-availability">' . wc_get_stock_html( $product ) . '</div>';
								?>
                            </div>

							<?php if ( ( $is_separate_atc || $is_custom_position ) && $custom_qty ) {
								echo '<div class="' . esc_attr( ( $plus_minus ? 'woobt-quantity woobt-quantity-plus-minus' : 'woobt-quantity' ) ) . '">';

								if ( $plus_minus ) {
									echo '<div class="woobt-quantity-input">';
									echo '<div class="woobt-quantity-input-minus">-</div>';
								}

								woocommerce_quantity_input( array(
									'input_name' => 'woobt_qty_0',
									'classes'    => array(
										'input-text',
										'woobt-qty',
										'woobt-this-qty',
										'qty',
										'text'
									)
								), $product );

								if ( $plus_minus ) {
									echo '<div class="woobt-quantity-input-plus">+</div>';
									echo '</div>';
								}

								echo '</div>';
							}

							if ( ! $is_separate_layout && ( self::get_setting( 'show_price', 'yes' ) !== 'no' ) ) { ?>
                                <div class="woobt-price">
                                    <div class="woobt-price-new">
										<?php
										if ( ! $separately && ( $discount = get_post_meta( $product_id, 'woobt_discount', true ) ) ) {
											$sale_price = $product->get_price() * ( 100 - (float) $discount ) / 100;
											echo wc_format_sale_price( $product->get_price(), $sale_price ) . $product->get_price_suffix( $sale_price );
										} else {
											echo $product->get_price_html();
										}
										?>
                                    </div>
                                    <div class="woobt-price-ori">
										<?php echo $product->get_price_html(); ?>
                                    </div>
                                </div>
							<?php }

							do_action( 'woobt_product_after', $product );
							?>
                        </div>
						<?php
					}

					return apply_filters( 'woobt_product_this_output', ob_get_clean(), $product, $is_custom_position );
				}

				function product_output( $item, $product_id = 0, $order = 1 ) {
					$pricing            = self::get_setting( 'pricing', 'sale_price' );
					$custom_qty         = apply_filters( 'woobt_custom_qty', get_post_meta( $product_id, 'woobt_custom_qty', true ) === 'on', $product_id );
					$checked_all        = apply_filters( 'woobt_checked_all', get_post_meta( $product_id, 'woobt_checked_all', true ) === 'on', $product_id );
					$separately         = apply_filters( 'woobt_separately', get_post_meta( $product_id, 'woobt_separately', true ) === 'on', $product_id );
					$layout             = self::get_setting( 'layout', 'default' );
					$is_separate_layout = $layout === 'separate';
					$plus_minus         = self::get_setting( 'plus_minus', 'no' ) === 'yes';

					$item_id      = $item['id'];
					$item_price   = $item['price'];
					$item_qty     = $item['qty'];
					$item_product = $item['product'];
					$item_qty_min = 1;
					$item_qty_max = 1000;

					if ( $custom_qty ) {
						if ( get_post_meta( $product_id, 'woobt_limit_each_min_default', true ) === 'on' ) {
							$item_qty_min = $item_qty;
						} else {
							$item_qty_min = absint( get_post_meta( $product_id, 'woobt_limit_each_min', true ) ?: 0 );
						}

						$item_qty_max = absint( get_post_meta( $product_id, 'woobt_limit_each_max', true ) ?: 1000 );

						if ( $item_qty < $item_qty_min ) {
							$item_qty = $item_qty_min;
						}

						if ( $item_qty > $item_qty_max ) {
							$item_qty = $item_qty_max;
						}
					}

					$checked_individual = apply_filters( 'woobt_checked_individual', false, $item_id, $product_id );
					$item_price         = apply_filters( 'woobt_item_price', ! $separately ? $item_price : '100%', $item_id, $product_id );
					$item_name          = apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product );

					ob_start();
					?>
                    <div class="woobt-product woobt-product-together"
                         data-order="<?php echo esc_attr( $order ); ?>"
                         data-id="<?php echo esc_attr( $item_product->is_type( 'variable' ) || ! $item_product->is_in_stock() ? 0 : $item_id ); ?>"
                         data-pid="<?php echo esc_attr( $item_id ); ?>"
                         data-name="<?php echo esc_attr( $item_name ); ?>"
                         data-new-price="<?php echo esc_attr( $item_price ); ?>"
                         data-price-suffix="<?php echo esc_attr( htmlentities( $item_product->get_price_suffix() ) ); ?>"
                         data-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_price', ( $pricing === 'sale_price' ) ? wc_get_price_to_display( $item_product ) : wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_regular_price() ) ), $item_product ) ); ?>"
                         data-regular-price="<?php echo esc_attr( apply_filters( 'woobt_item_data_regular_price', wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_regular_price() ) ), $item_product ) ); ?>"
                         data-qty="<?php echo esc_attr( $item_qty ); ?>"
                         data-o_qty="<?php echo esc_attr( $item_qty ); ?>">

						<?php do_action( 'woobt_product_before', $item_product, $order ); ?>

                        <div class="woobt-choose">
                            <label for="<?php echo esc_attr( 'woobt_checkbox_' . $order ); ?>"><?php echo esc_html( $item_name ); ?></label>
                            <input id="<?php echo esc_attr( 'woobt_checkbox_' . $order ); ?>" class="woobt-checkbox"
                                   type="checkbox"
                                   value="<?php echo esc_attr( $item_id ); ?>" <?php echo esc_attr( ! $item_product->is_in_stock() ? 'disabled' : '' ); ?> <?php echo esc_attr( $item_product->is_in_stock() && ( $checked_all || $checked_individual ) ? 'checked' : '' ); ?>/>
                            <span class="checkmark"></span>
                        </div>

						<?php if ( self::get_setting( 'show_thumb', 'yes' ) !== 'no' ) {
							echo '<div class="woobt-thumb">';

							if ( self::get_setting( 'link', 'yes' ) !== 'no' ) {
								echo '<a ' . ( self::get_setting( 'link', 'yes' ) === 'yes_popup' ? 'class="woosq-link" data-id="' . $item_id . '" data-context="woobt"' : '' ) . ' href="' . $item_product->get_permalink() . '" ' . ( self::get_setting( 'link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
							}
							?>
                            <div class="woobt-thumb-ori">
								<?php echo $item_product->get_image( self::$image_size ); ?>
                            </div>
                            <div class="woobt-thumb-new"></div>
							<?php
							if ( self::get_setting( 'link', 'yes' ) !== 'no' ) {
								echo '</a>';
							}

							echo '</div>';
						} ?>

                        <div class="woobt-title">
                            <span class="woobt-title-inner">
                                <?php if ( ! $custom_qty ) {
	                                $item_product_qty = '<span class="woobt-qty-num"><span class="woobt-qty">' . $item_qty . '</span> × </span>';
                                } else {
	                                $item_product_qty = '';
                                }

                                echo apply_filters( 'woobt_product_qty', $item_product_qty, $item_qty, $item_product );

                                if ( $item_product->is_in_stock() ) {
	                                $item_product_name = apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product );
                                } else {
	                                $item_product_name = '<s>' . apply_filters( 'woobt_product_get_name', $item_product->get_name(), $item_product ) . '</s>';
                                }

                                if ( self::get_setting( 'link', 'yes' ) !== 'no' ) {
	                                $item_product_name = '<a ' . ( self::get_setting( 'link', 'yes' ) === 'yes_popup' ? 'class="woosq-link" data-id="' . $item_id . '" data-context="woobt"' : '' ) . ' href="' . $item_product->get_permalink() . '" ' . ( self::get_setting( 'link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . $item_product_name . '</a>';
                                } else {
	                                $item_product_name = '<span>' . $item_product_name . '</span>';
                                }

                                echo apply_filters( 'woobt_product_name', $item_product_name, $item_product );
                                ?>
                            </span>

							<?php if ( $is_separate_layout && ( self::get_setting( 'show_price', 'yes' ) !== 'no' ) ) { ?>
                                <span class="woobt-price">
                                    <span class="woobt-price-new"></span>
                                    <span class="woobt-price-ori">
                                        <?php
                                        if ( ! $separately && ( $item_price !== '100%' ) ) {
	                                        if ( $item_product->is_type( 'variable' ) ) {
		                                        $item_ori_price_min = ( $pricing === 'sale_price' ) ? $item_product->get_variation_price( 'min', true ) : $item_product->get_variation_regular_price( 'min', true );
		                                        $item_ori_price_max = ( $pricing === 'sale_price' ) ? $item_product->get_variation_price( 'max', true ) : $item_product->get_variation_regular_price( 'max', true );
		                                        $item_new_price_min = self::new_price( $item_ori_price_min, $item_price );
		                                        $item_new_price_max = self::new_price( $item_ori_price_max, $item_price );

		                                        if ( $item_new_price_min < $item_new_price_max ) {
			                                        $item_product_price = wc_format_price_range( $item_new_price_min, $item_new_price_max );
		                                        } else {
			                                        $item_product_price = wc_format_sale_price( $item_ori_price_min, $item_new_price_min );
		                                        }
	                                        } else {
		                                        $item_ori_price = ( $pricing === 'sale_price' ) ? wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_price() ) ) : wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_regular_price() ) );
		                                        $item_new_price = self::new_price( $item_ori_price, $item_price );

		                                        if ( $item_new_price < $item_ori_price ) {
			                                        $item_product_price = wc_format_sale_price( $item_ori_price, $item_new_price );
		                                        } else {
			                                        $item_product_price = wc_price( $item_new_price );
		                                        }
	                                        }

	                                        $item_product_price .= $item_product->get_price_suffix();
                                        } else {
	                                        $item_product_price = $item_product->get_price_html();
                                        }

                                        echo apply_filters( 'woobt_product_price', $item_product_price, $item_product, $item );
                                        ?>
                                    </span>
                                </span>
								<?php
							}

							if ( self::get_setting( 'show_description', 'no' ) === 'yes' ) {
								echo '<div class="woobt-description">' . apply_filters( 'woobt_product_short_description', $item_product->get_short_description(), $item_product ) . '</div>';
							}

							if ( $item_product->is_type( 'variable' ) ) {
								if ( ( self::get_setting( 'variations_selector', 'default' ) === 'wpc_radio' || self::get_setting( 'variations_selector', 'default' ) === 'woovr' ) && class_exists( 'WPClever_Woovr' ) ) {
									echo '<div class="wpc_variations_form">';
									// use class name wpc_variations_form to prevent found_variation in woovr
									WPClever_Woovr::woovr_variations_form( $item_product );
									echo '</div>';
								} else {
									$attributes           = $item_product->get_variation_attributes();
									$available_variations = $item_product->get_available_variations();

									if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {
										echo '<div class="variations_form" data-product_id="' . absint( $item_product->get_id() ) . '" data-product_variations="' . htmlspecialchars( wp_json_encode( $available_variations ) ) . '">';
										echo '<div class="variations">';

										foreach ( $attributes as $attribute_name => $options ) { ?>
                                            <div class="variation">
                                                <div class="label">
													<?php echo wc_attribute_label( $attribute_name ); ?>
                                                </div>
                                                <div class="select">
													<?php
													$selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( stripslashes( urldecode( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ) ) : $item_product->get_variation_default_attribute( $attribute_name );
													wc_dropdown_variation_attribute_options( array(
														'options'          => $options,
														'attribute'        => $attribute_name,
														'product'          => $item_product,
														'selected'         => $selected,
														'show_option_none' => sprintf( self::localization( 'choose', esc_html__( 'Choose %s', 'woo-bought-together' ) ), wc_attribute_label( $attribute_name ) )
													) );
													?>
                                                </div>
                                            </div>
										<?php }

										echo '<div class="reset">' . apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . self::localization( 'clear', esc_html__( 'Clear', 'woo-bought-together' ) ) . '</a>' ) . '</div>';
										echo '</div>';
										echo '</div>';

										if ( self::get_setting( 'show_description', 'no' ) === 'yes' ) {
											echo '<div class="woobt-variation-description"></div>';
										}
									}
								}
							}

							echo '<div class="woobt-availability">' . apply_filters( 'woobt_product_availability', wc_get_stock_html( $item_product ), $item_product ) . '</div>';
							?>
                        </div>

						<?php if ( $custom_qty ) {
							echo '<div class="' . esc_attr( ( $plus_minus ? 'woobt-quantity woobt-quantity-plus-minus' : 'woobt-quantity' ) ) . '">';

							if ( $plus_minus ) {
								echo '<div class="woobt-quantity-input">';
								echo '<div class="woobt-quantity-input-minus">-</div>';
							}

							woocommerce_quantity_input( array(
								'classes'     => array( 'input-text', 'woobt-qty', 'qty', 'text' ),
								'input_value' => $item_qty,
								'min_value'   => $item_qty_min,
								'max_value'   => $item_qty_max,
								'input_name'  => 'woobt_qty_' . $order,
								'woobt_qty'   => array(
									'input_value' => $item_qty,
									'min_value'   => $item_qty_min,
									'max_value'   => $item_qty_max
								)
								// compatible with WPC Product Quantity
							), $item_product );

							if ( $plus_minus ) {
								echo '<div class="woobt-quantity-input-plus">+</div>';
								echo '</div>';
							}

							echo '</div>';
						}

						if ( ! $is_separate_layout && ( self::get_setting( 'show_price', 'yes' ) !== 'no' ) ) { ?>
                            <div class="woobt-price">
                                <div class="woobt-price-new"></div>
                                <div class="woobt-price-ori">
									<?php
									if ( ! $separately && ( $item_price !== '100%' ) ) {
										if ( $item_product->is_type( 'variable' ) ) {
											$item_ori_price_min = ( $pricing === 'sale_price' ) ? $item_product->get_variation_price( 'min', true ) : $item_product->get_variation_regular_price( 'min', true );
											$item_ori_price_max = ( $pricing === 'sale_price' ) ? $item_product->get_variation_price( 'max', true ) : $item_product->get_variation_regular_price( 'max', true );
											$item_new_price_min = self::new_price( $item_ori_price_min, $item_price );
											$item_new_price_max = self::new_price( $item_ori_price_max, $item_price );

											if ( $item_new_price_min < $item_new_price_max ) {
												$item_product_price = wc_format_price_range( $item_new_price_min, $item_new_price_max );
											} else {
												$item_product_price = wc_format_sale_price( $item_ori_price_min, $item_new_price_min );
											}
										} else {
											$item_ori_price = ( $pricing === 'sale_price' ) ? wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_price() ) ) : wc_get_price_to_display( $item_product, array( 'price' => $item_product->get_regular_price() ) );
											$item_new_price = self::new_price( $item_ori_price, $item_price );

											if ( $item_new_price < $item_ori_price ) {
												$item_product_price = wc_format_sale_price( $item_ori_price, $item_new_price );
											} else {
												$item_product_price = wc_price( $item_new_price );
											}
										}

										$item_product_price .= $item_product->get_price_suffix();
									} else {
										$item_product_price = $item_product->get_price_html();
									}

									echo apply_filters( 'woobt_product_price', $item_product_price, $item_product, $item );
									?>
                                </div>
                            </div>
						<?php }

						do_action( 'woobt_product_after', $item_product, $order );
						?>
                    </div>
					<?php

					return apply_filters( 'woobt_product_output', ob_get_clean(), $item, $product_id, $order );
				}

				function get_ids( $product_id, $context = 'display' ) {
					$ids = get_post_meta( $product_id, 'woobt_ids', true );

					return apply_filters( 'woobt_get_ids', $ids, $product_id, $context );
				}

				function get_items( $ids, $product_id = 0, $context = 'view' ) {
					$items = array();
					$ids   = self::clean_ids( $ids );

					if ( ! empty( $ids ) ) {
						$_items = explode( ',', $ids );

						if ( is_array( $_items ) && count( $_items ) > 0 ) {
							foreach ( $_items as $_item ) {
								$_item_data    = explode( '/', $_item );
								$_item_id      = apply_filters( 'woobt_item_id', absint( $_item_data[0] ?: 0 ) );
								$_item_product = wc_get_product( $_item_id );

								if ( ! $_item_product || ( $_item_product->get_status() === 'trash' ) ) {
									continue;
								}

								if ( ( $context === 'view' ) && ( ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) && ( ! $_item_product->is_purchasable() || ! $_item_product->is_in_stock() ) ) ) {
									continue;
								}

								$items[] = array(
									'id'    => $_item_id,
									'price' => isset( $_item_data[1] ) ? self::format_price( $_item_data[1] ) : '100%',
									'qty'   => (float) ( isset( $_item_data[2] ) ? $_item_data[2] : 1 ),
									'attrs' => isset( $_item_data[3] ) ? (array) json_decode( rawurldecode( $_item_data[3] ) ) : array()
								);
							}
						}
					}

					$items = apply_filters( 'woobt_get_items', $items, $ids, $product_id, $context );

					if ( $items && is_array( $items ) && count( $items ) > 0 ) {
						return $items;
					}

					return false;
				}

				function search_sku( $query ) {
					if ( $query->is_search && isset( $query->query['is_woobt'] ) ) {
						global $wpdb;
						$sku = sanitize_text_field( $query->query['s'] );
						$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value = %s;", $sku ) );

						if ( ! $ids ) {
							return;
						}

						unset( $query->query['s'], $query->query_vars['s'] );
						$query->query['post__in'] = array();

						foreach ( $ids as $id ) {
							$post = get_post( $id );

							if ( $post->post_type === 'product_variation' ) {
								$query->query['post__in'][]      = $post->post_parent;
								$query->query_vars['post__in'][] = $post->post_parent;
							} else {
								$query->query_vars['post__in'][] = $post->ID;
							}
						}
					}
				}

				function search_exact( $query ) {
					if ( $query->is_search && isset( $query->query['is_woobt'] ) ) {
						$query->set( 'exact', true );
					}
				}

				function search_sentence( $query ) {
					if ( $query->is_search && isset( $query->query['is_woobt'] ) ) {
						$query->set( 'sentence', true );
					}
				}

				public static function clean_ids( $ids ) {
					//$ids = preg_replace( '/[^.%,\/0-9]/', '', $ids );

					return apply_filters( 'woobt_clean_ids', $ids );
				}

				public static function format_price( $price ) {
					// format price to percent or number
					$price = preg_replace( '/[^.%0-9]/', '', $price );

					return apply_filters( 'woobt_format_price', $price );
				}

				public static function new_price( $old_price, $new_price ) {
					if ( strpos( $new_price, '%' ) !== false ) {
						$calc_price = ( (float) $new_price * $old_price ) / 100;
					} else {
						$calc_price = $new_price;
					}

					return apply_filters( 'woobt_new_price', $calc_price, $old_price );
				}

				function wpml_item_id( $id ) {
					return apply_filters( 'wpml_object_id', $id, 'product', true );
				}

				public static function localization( $key = '', $default = '' ) {
					$str = '';

					if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
						$str = self::$localization[ $key ];
					} elseif ( ! empty( $default ) ) {
						$str = $default;
					}

					return apply_filters( 'woobt_localization_' . $key, $str );
				}

				function product_filter( $filters ) {
					$filters['woobt'] = [ $this, 'product_filter_callback' ];

					return $filters;
				}

				function product_filter_callback() {
					$woobt  = isset( $_REQUEST['woobt'] ) ? wc_clean( wp_unslash( $_REQUEST['woobt'] ) ) : false;
					$output = '<select name="woobt"><option value="">' . esc_html__( 'Bought together', 'woo-bought-together' ) . '</option>';
					$output .= '<option value="yes" ' . selected( $woobt, 'yes', false ) . '>' . esc_html__( 'With associated products', 'woo-bought-together' ) . '</option>';
					$output .= '<option value="no" ' . selected( $woobt, 'no', false ) . '>' . esc_html__( 'Without associated products', 'woo-bought-together' ) . '</option>';
					$output .= '</select>';
					echo $output;
				}

				function apply_product_filter( $query ) {
					global $pagenow;

					if ( $query->is_admin && $pagenow == 'edit.php' && isset( $_GET['woobt'] ) && $_GET['woobt'] != '' && $_GET['post_type'] == 'product' ) {
						$meta_query = (array) $query->get( 'meta_query' );

						if ( $_GET['woobt'] === 'yes' ) {
							$meta_query[] = array(
								'relation' => 'AND',
								array(
									'key'     => 'woobt_ids',
									'compare' => 'EXISTS'
								),
								array(
									'key'     => 'woobt_ids',
									'value'   => '',
									'compare' => '!='
								),
							);
						} else {
							$meta_query[] = array(
								'relation' => 'OR',
								array(
									'key'     => 'woobt_ids',
									'compare' => 'NOT EXISTS'
								),
								array(
									'key'     => 'woobt_ids',
									'value'   => '',
									'compare' => '=='
								),
							);
						}

						$query->set( 'meta_query', $meta_query );
					}
				}
			}

			return WPCleverWoobt::instance();
		}
	}
}

if ( ! function_exists( 'woobt_notice_wc' ) ) {
	function woobt_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Frequently Bought Together</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
