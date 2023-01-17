<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWoosb' ) && class_exists( 'WC_Product' ) ) {
	class WPCleverWoosb {
		protected static $instance = null;
		protected static $image_size = 'woocommerce_thumbnail';
		protected static $types = array(
			'bundle',
			'woosb',
			'composite',
			'grouped',
			'woosg',
			'external'
		);

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
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
			add_action( 'wp_ajax_woosb_update_search_settings', [ $this, 'update_search_settings' ] );
			add_action( 'wp_ajax_woosb_get_search_results', [ $this, 'get_search_results' ] );

			// Add to selector
			add_filter( 'product_type_selector', [ $this, 'product_type_selector' ] );

			// Product data tabs
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );

			// Product tab
			if ( ( WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' ) === 'tab' ) || ( WPCleverWoosb_Helper::get_setting( 'bundles_position', 'no' ) === 'tab' ) ) {
				add_filter( 'woocommerce_product_tabs', [ $this, 'product_tabs' ] );
			}

			// Bundled products position
			switch ( WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' ) ) {
				case 'below_title';
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 6 );
					break;
				case 'below_price':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 11 );
					break;
				case 'below_excerpt';
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 21 );
					break;
			}

			// Bundles position
			switch ( WPCleverWoosb_Helper::get_setting( 'bundles_position', 'no' ) ) {
				case 'above':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundles' ], 29 );
					break;
				case 'below':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundles' ], 31 );
					break;
			}

			// Product data panels
			add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );
			add_action( 'woocommerce_process_product_meta_woosb', [ $this, 'process_product_meta_woosb' ] );

			// Product price class
			add_filter( 'woocommerce_product_price_class', [ $this, 'product_price_class' ] );

			// Add to cart form & button
			add_action( 'woocommerce_woosb_add_to_cart', [ $this, 'add_to_cart_form' ] );
			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_to_cart_button' ] );

			// Add to cart
			add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [ $this, 'found_in_cart' ], 10, 2 );

			if ( WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' ) !== 'yes' ) {
				// Validation
				add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 2 );
			}

			add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 2 );
			add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
			add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 2 );

			// Cart item
			add_filter( 'woocommerce_cart_item_name', [ $this, 'cart_item_name' ], 10, 2 );
			add_filter( 'woocommerce_cart_item_quantity', [ $this, 'cart_item_quantity' ], 10, 3 );
			add_filter( 'woocommerce_cart_item_remove_link', [ $this, 'cart_item_remove_link' ], 10, 2 );
			add_filter( 'woocommerce_cart_contents_count', [ $this, 'cart_contents_count' ] );
			add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 10, 2 );
			add_filter( 'woocommerce_cart_item_price', [ $this, 'cart_item_price' ], 10, 2 );
			add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'cart_item_subtotal' ], 10, 2 );

			// Order
			add_filter( 'woocommerce_get_item_count', [ $this, 'get_item_count' ], 10, 3 );

			// Mini cart item visible
			add_filter( 'woocommerce_widget_cart_item_visible', [ $this, 'mini_cart_item_visible' ], 10, 2 );

			// Cart item visible
			add_filter( 'woocommerce_cart_item_visible', [ $this, 'cart_item_visible' ], 10, 2 );
			add_filter( 'woocommerce_checkout_cart_item_visible', [ $this, 'cart_item_visible' ], 10, 2 );

			// Order item visible
			add_filter( 'woocommerce_order_item_visible', [ $this, 'order_item_visible' ], 10, 2 );

			// Item class
			if ( WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' ) !== 'yes' ) {
				add_filter( 'woocommerce_cart_item_class', [ $this, 'cart_item_class' ], 10, 2 );
				add_filter( 'woocommerce_mini_cart_item_class', [ $this, 'cart_item_class' ], 10, 2 );
				add_filter( 'woocommerce_order_item_class', [ $this, 'cart_item_class' ], 10, 2 );
			}

			// Get item data
			if ( WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' ) === 'yes_text' ) {
				add_filter( 'woocommerce_get_item_data', [ $this, 'get_item_data' ], 10, 2 );
			}

			// Order item
			add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'create_order_line_item' ], 10, 3 );
			add_filter( 'woocommerce_order_item_name', [ $this, 'cart_item_name' ], 10, 2 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', [ $this, 'formatted_line_subtotal' ], 10, 2 );
			add_action( 'woocommerce_order_item_meta_start', [ $this, 'before_order_itemmeta' ], 10, 2 );

			// Admin order
			add_action( 'woocommerce_ajax_add_order_item_meta', [ $this, 'ajax_add_order_item_meta' ], 10, 3 );
			add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'hidden_order_itemmeta' ] );
			add_action( 'woocommerce_before_order_itemmeta', [ $this, 'before_order_itemmeta' ], 10, 2 );

			// Undo remove
			add_action( 'woocommerce_restore_cart_item', [ $this, 'restore_cart_item' ] );

			// Add settings link
			add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// Loop add-to-cart
			add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'loop_add_to_cart_link' ], 99, 2 );

			// Before calculate totals
			add_action( 'woocommerce_before_mini_cart_contents', [ $this, 'before_mini_cart_contents' ] );
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 9999 );

			// Shipping
			add_filter( 'woocommerce_cart_shipping_packages', [ $this, 'cart_shipping_packages' ], 9, 1 );
			add_filter( 'woocommerce_cart_contents_weight', [ $this, 'cart_contents_weight' ], 9, 1 );

			// Price html
			add_filter( 'woocommerce_get_price_html', [ $this, 'get_price_html' ], 99, 2 );

			// Order again
			add_filter( 'woocommerce_order_again_cart_item_data', [ $this, 'order_again_cart_item_data' ], 10, 2 );
			add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'cart_loaded_from_session' ] );

			// Coupons
			add_filter( 'woocommerce_coupon_is_valid_for_product', [ $this, 'coupon_is_valid_for_product' ], 10, 4 );

			// Admin
			add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );

			// Bulk action
			add_action( 'current_screen', [ $this, 'bulk_actions' ] );

			// Emails
			add_action( 'woocommerce_no_stock_notification', [ $this, 'no_stock_notification' ], 99 );
			add_action( 'woocommerce_low_stock_notification', [ $this, 'low_stock_notification' ], 99 );

			// Search filters
			if ( WPCleverWoosb_Helper::get_setting( 'search_sku', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_sku' ], 99 );
			}

			if ( WPCleverWoosb_Helper::get_setting( 'search_exact', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_exact' ], 99 );
			}

			if ( WPCleverWoosb_Helper::get_setting( 'search_sentence', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_sentence' ], 99 );
			}

			// WPML
			if ( function_exists( 'wpml_loaded' ) ) {
				add_filter( 'woosb_item_id', [ $this, 'wpml_item_id' ], 99 );
			}
		}

		function init() {
			// image size
			self::$image_size = apply_filters( 'woosb_image_size', self::$image_size );

			// shortcode
			add_shortcode( 'woosb_form', [ $this, 'shortcode_form' ] );
			add_shortcode( 'woosb_bundled', [ $this, 'shortcode_bundled' ] );
			add_shortcode( 'woosb_bundles', [ $this, 'shortcode_bundles' ] );
		}

		function available_variation( $data, $variable, $variation ) {
			if ( $image_id = $variation->get_image_id() ) {
				$data['woosb_image'] = wp_get_attachment_image( $image_id, self::$image_size );
			}

			return $data;
		}

		function register_settings() {
			// settings
			register_setting( 'woosb_settings', 'woosb_settings' );
			// localization
			register_setting( 'woosb_localization', 'woosb_localization' );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', esc_html__( 'WPC Product Bundles', 'woo-product-bundle' ), esc_html__( 'Product Bundles', 'woo-product-bundle' ), 'manage_options', 'wpclever-woosb', array(
				$this,
				'admin_menu_content'
			) );
		}

		function admin_menu_content() {
			add_thickbox();
			$active_tab     = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
			$active_section = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'none';
			$settings_class = 'wpclever_settings_page_content wpclever_settings_tab_' . $active_tab . ' wpclever_settings_section_' . $active_section;
			?>
            <div class="wpclever_settings_page wrap">
                <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Product Bundles', 'woo-product-bundle' ) . ' ' . WOOSB_VERSION; ?></h1>
                <div class="wpclever_settings_page_desc about-text">
                    <p>
						<?php printf( esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'woo-product-bundle' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                        <br/>
                        <a href="<?php echo esc_url( WOOSB_REVIEWS ); ?>"
                           target="_blank"><?php esc_html_e( 'Reviews', 'woo-product-bundle' ); ?></a> | <a
                                href="<?php echo esc_url( WOOSB_CHANGELOG ); ?>"
                                target="_blank"><?php esc_html_e( 'Changelog', 'woo-product-bundle' ); ?></a>
                        | <a href="<?php echo esc_url( WOOSB_DISCUSSION ); ?>"
                             target="_blank"><?php esc_html_e( 'Discussion', 'woo-product-bundle' ); ?></a>
                    </p>
                </div>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Settings updated.', 'woo-product-bundle' ); ?></p>
                    </div>
				<?php } ?>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=how' ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'how' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'How to use?', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=settings' ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'settings' && $active_section === 'none' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Settings', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=localization' ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Localization', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=settings&section=compatible' ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'settings' && $active_section === 'compatible' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Compatible', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( WOOSB_DOCS ); ?>" class="nav-tab" target="_blank">
							<?php esc_html_e( 'Docs', 'woo-product-bundle' ); ?>
                        </a>
                        <!--
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=premium' ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>"
                           style="color: #c9356e">
                            <?php esc_html_e( 'Premium Version', 'woo-product-bundle' ); ?>
                        </a>
                        -->
                        <a href="<?php echo esc_url( WOOSB_SUPPORT ); ?>" class="nav-tab" target="_blank">
							<?php esc_html_e( 'Support', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=wpclever-kit' ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'woo-product-bundle' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="<?php echo esc_attr( $settings_class ); ?>">
					<?php if ( $active_tab === 'how' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>
								<?php esc_html_e( 'When creating the product, please choose product data is "Smart Bundle" then you can see the search field to start search and add products to the bundle.', 'woo-product-bundle' ); ?>
                            </p>
                            <p>
                                <img src="<?php echo WOOSB_URI; ?>assets/images/how-01.jpg"/>
                            </p>
                        </div>
					<?php } elseif ( $active_tab === 'settings' ) {
						$price_format          = WPCleverWoosb_Helper::get_setting( 'price_format', 'from_min' );
						$price_from            = WPCleverWoosb_Helper::get_setting( 'bundled_price_from', 'sale_price' );
						$bundled_position      = WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' );
						$variations_selector   = WPCleverWoosb_Helper::get_setting( 'variations_selector', 'default' );
						$bundled_thumb         = WPCleverWoosb_Helper::get_setting( 'bundled_thumb', 'yes' );
						$bundled_qty           = WPCleverWoosb_Helper::get_setting( 'bundled_qty', 'yes' );
						$bundled_desc          = WPCleverWoosb_Helper::get_setting( 'bundled_description', 'no' );
						$bundled_price         = WPCleverWoosb_Helper::get_setting( 'bundled_price', 'price' );
						$bundled_link          = WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' );
						$plus_minus            = WPCleverWoosb_Helper::get_setting( 'plus_minus', 'no' );
						$change_image          = WPCleverWoosb_Helper::get_setting( 'change_image', 'yes' );
						$change_price          = WPCleverWoosb_Helper::get_setting( 'change_price', 'yes' );
						$bundles_position      = WPCleverWoosb_Helper::get_setting( 'bundles_position', 'no' );
						$coupon_restrictions   = WPCleverWoosb_Helper::get_setting( 'coupon_restrictions', 'no' );
						$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' );
						$contents_count        = WPCleverWoosb_Helper::get_setting( 'cart_contents_count', 'bundle' );
						$hide_bundle_name      = WPCleverWoosb_Helper::get_setting( 'hide_bundle_name', 'no' );
						$hide_bundled          = WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' );
						$hide_bundled_mc       = WPCleverWoosb_Helper::get_setting( 'hide_bundled_mini_cart', 'no' );
						$wcpdf_hide_bundles    = WPCleverWoosb_Helper::get_setting( 'compatible_wcpdf_hide_bundles', 'no' );
						$wcpdf_hide_bundled    = WPCleverWoosb_Helper::get_setting( 'compatible_wcpdf_hide_bundled', 'no' );
						$pklist_hide_bundles   = WPCleverWoosb_Helper::get_setting( 'compatible_pklist_hide_bundles', 'no' );
						$pklist_hide_bundled   = WPCleverWoosb_Helper::get_setting( 'compatible_pklist_hide_bundled', 'no' );
						?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'General', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Price format', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[price_format]" class="woosb_price_format">
                                            <option value="from_min" <?php selected( $price_format, 'from_min' ); ?>><?php esc_html_e( 'From min price', 'woo-product-bundle' ); ?></option>
                                            <option value="min_only" <?php selected( $price_format, 'min_only' ); ?>><?php esc_html_e( 'Min price only', 'woo-product-bundle' ); ?></option>
                                            <option value="min_max" <?php selected( $price_format, 'min_max' ); ?>><?php esc_html_e( 'Min - max', 'woo-product-bundle' ); ?></option>
                                            <option value="normal" <?php selected( $price_format, 'normal' ); ?>><?php esc_html_e( 'Regular and sale price', 'woo-product-bundle' ); ?></option>
                                            <option value="custom" <?php selected( $price_format, 'custom' ); ?>><?php esc_html_e( 'Custom', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Choose the price format for bundle on the shop/archive page.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="woosb_tr_show_if_price_format_custom">
                                    <th><?php esc_html_e( 'Default custom display price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" class="regular-text"
                                               name="woosb_settings[price_format_custom]"
                                               placeholder="<?php esc_attr_e( 'before %s after', 'woo-product-bundle' ); ?>"
                                               value="<?php echo WPCleverWoosb_Helper::get_setting( 'price_format_custom', esc_html__( 'before %s after', 'woo-product-bundle' ) ); ?>"/>
                                        <span class="description"><?php esc_html_e( 'Use %s to show the dynamic price between your custom text. You still can overwrite it in each bundle.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Calculate bundled prices', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_price_from]">
                                            <option value="sale_price" <?php selected( $price_from, 'sale_price' ); ?>><?php esc_html_e( 'from Sale price', 'woo-product-bundle' ); ?></option>
                                            <option value="regular_price" <?php selected( $price_from, 'regular_price' ); ?>><?php esc_html_e( 'from Regular price', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Bundled pricing methods: from Sale price (default) or Regular price.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Bundled products', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Position', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_position]">
                                            <option value="above" <?php selected( $bundled_position, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'woo-product-bundle' ); ?></option>
                                            <option value="below" <?php selected( $bundled_position, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'woo-product-bundle' ); ?></option>
                                            <option value="below_title" <?php selected( $bundled_position, 'below_title' ); ?>><?php esc_html_e( 'Under the title', 'woo-product-bundle' ); ?></option>
                                            <option value="below_price" <?php selected( $bundled_position, 'below_price' ); ?>><?php esc_html_e( 'Under the price', 'woo-product-bundle' ); ?></option>
                                            <option value="below_excerpt" <?php selected( $bundled_position, 'below_excerpt' ); ?>><?php esc_html_e( 'Under the excerpt', 'woo-product-bundle' ); ?></option>
                                            <option value="tab" <?php selected( $bundled_position, 'tab' ); ?>><?php esc_html_e( 'In a new tab', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_position, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Choose the position to show the bundled products list.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Variations selector', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[variations_selector]">
                                            <option value="default" <?php selected( $variations_selector, 'default' ); ?>><?php esc_html_e( 'Default', 'woo-product-bundle' ); ?></option>
                                            <option value="woovr" <?php selected( $variations_selector, 'woovr' ); ?>><?php esc_html_e( 'Use WPC Variations Radio Buttons', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description">If you choose "Use WPC Variations Radio Buttons", please install <a
                                                    href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=wpc-variations-radio-buttons&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                    class="thickbox"
                                                    title="Install WPC Variations Radio Buttons">WPC Variations Radio Buttons</a> to make it work.</span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show thumbnail', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_thumb]">
                                            <option value="yes" <?php selected( $bundled_thumb, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_thumb, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show quantity', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_qty]">
                                            <option value="yes" <?php selected( $bundled_qty, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_qty, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show short description', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_description]">
                                            <option value="yes" <?php selected( $bundled_desc, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_desc, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_price]">
                                            <option value="price" <?php selected( $bundled_price, 'price' ); ?>><?php esc_html_e( 'Price', 'woo-product-bundle' ); ?></option>
                                            <option value="subtotal" <?php selected( $bundled_price, 'subtotal' ); ?>><?php esc_html_e( 'Subtotal', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Link to individual product', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundled_link]">
                                            <option value="yes" <?php selected( $bundled_link, 'yes' ); ?>><?php esc_html_e( 'Yes, open in the same tab', 'woo-product-bundle' ); ?></option>
                                            <option value="yes_blank" <?php selected( $bundled_link, 'yes_blank' ); ?>><?php esc_html_e( 'Yes, open in the new tab', 'woo-product-bundle' ); ?></option>
                                            <option value="yes_popup" <?php selected( $bundled_link, 'yes_popup' ); ?>><?php esc_html_e( 'Yes, open quick view popup', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundled_link, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description">If you choose "Open quick view popup", please install <a
                                                    href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                    class="thickbox" title="Install WPC Smart Quick View">WPC Smart Quick View</a> to make it work.</span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show plus/minus button', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[plus_minus]">
                                            <option value="yes" <?php selected( $plus_minus, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $plus_minus, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Show the plus/minus button for the quantity input.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Change image', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[change_image]">
                                            <option value="yes" <?php selected( $change_image, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $change_image, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Change the main product image when choosing the variation of bundled products.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Change price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[change_price]" class="woosb_change_price">
                                            <option value="yes" <?php selected( $change_price, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="yes_custom" <?php selected( $change_price, 'yes_custom' ); ?>><?php esc_html_e( 'Yes, custom selector', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $change_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <input type="text" name="woosb_settings[change_price_custom]"
                                               value="<?php echo WPCleverWoosb_Helper::get_setting( 'change_price_custom', '.summary > .price' ); ?>"
                                               placeholder=".summary > .price" class="woosb_change_price_custom"/>
                                        <span class="description"><?php esc_html_e( 'Change the main product price when choosing the variation of bundled products. It uses JavaScript to change product price so it is very dependent on theme’s HTML. If it cannot find and update the product price, please contact us and we can help you find the right selector or adjust the JS file.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th>
										<?php esc_html_e( 'Bundles', 'woo-product-bundle' ); ?>
                                    </th>
                                    <td>
										<?php esc_html_e( 'Settings for bundles on the bundled product page.', 'woo-product-bundle' ); ?>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Position', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[bundles_position]">
                                            <option value="above" <?php selected( $bundles_position, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'woo-product-bundle' ); ?></option>
                                            <option value="below" <?php selected( $bundles_position, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'woo-product-bundle' ); ?></option>
                                            <option value="tab" <?php selected( $bundles_position, 'tab' ); ?>><?php esc_html_e( 'In a new tab', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $bundles_position, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Choose the position to show the bundles list.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th>
										<?php esc_html_e( 'Above text', 'woo-product-bundle' ); ?>
                                    </th>
                                    <td>
                                        <textarea name="woosb_settings[bundles_before_text]"
                                                  class="large-text"><?php echo stripslashes( WPCleverWoosb_Helper::get_setting( 'bundles_before_text' ) ); ?></textarea>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th>
										<?php esc_html_e( 'Under text', 'woo-product-bundle' ); ?>
                                    </th>
                                    <td>
                                        <textarea name="woosb_settings[bundles_after_text]"
                                                  class="large-text"><?php echo stripslashes( WPCleverWoosb_Helper::get_setting( 'bundles_after_text' ) ); ?></textarea>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Cart & Checkout', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Coupon restrictions', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[coupon_restrictions]">
                                            <option value="no" <?php selected( $coupon_restrictions, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            <option value="bundles" <?php selected( $coupon_restrictions, 'bundles' ); ?>><?php esc_html_e( 'Exclude bundles', 'woo-product-bundle' ); ?></option>
                                            <option value="bundled" <?php selected( $coupon_restrictions, 'bundled' ); ?>><?php esc_html_e( 'Exclude bundled products', 'woo-product-bundle' ); ?></option>
                                            <option value="both" <?php selected( $coupon_restrictions, 'both' ); ?>><?php esc_html_e( 'Exclude both bundles and bundled products', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Choose products you want to exclude from coupons.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Exclude un-purchasable products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[exclude_unpurchasable]">
                                            <option value="yes" <?php selected( $exclude_unpurchasable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $exclude_unpurchasable, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Make the bundle still purchasable when one of the bundled products is un-purchasable. These bundled products are excluded from the orders.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Cart contents count', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[cart_contents_count]">
                                            <option value="bundle" <?php selected( $contents_count, 'bundle' ); ?>><?php esc_html_e( 'Bundles only', 'woo-product-bundle' ); ?></option>
                                            <option value="bundled_products" <?php selected( $contents_count, 'bundled_products' ); ?>><?php esc_html_e( 'Bundled products only', 'woo-product-bundle' ); ?></option>
                                            <option value="both" <?php selected( $contents_count, 'both' ); ?>><?php esc_html_e( 'Both bundles and bundled products', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundle name before bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[hide_bundle_name]">
                                            <option value="yes" <?php selected( $hide_bundle_name, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $hide_bundle_name, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundled products on cart & checkout page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[hide_bundled]">
                                            <option value="yes" <?php selected( $hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes, just show the main product', 'woo-product-bundle' ); ?></option>
                                            <option value="yes_text" <?php selected( $hide_bundled, 'yes_text' ); ?>><?php esc_html_e( 'Yes, but show bundled product names under the main product', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundled products on mini-cart', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[hide_bundled_mini_cart]">
                                            <option value="yes" <?php selected( $hide_bundled_mc, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $hide_bundled_mc, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                        <span class="description"><?php esc_html_e( 'Hide bundled products, just show the main product on mini-cart.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Search', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
								<?php self::search_settings(); ?>
                                <tr class="heading show_if_section_compatible">
                                    <th colspan="2">
										<?php esc_html_e( 'WooCommerce PDF Invoices & Packing Slips', 'woo-product-bundle' ); ?>
                                        <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/"
                                           target="_blank"><span class="dashicons dashicons-external"></span></a>
                                    </th>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundles', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[compatible_wcpdf_hide_bundles]">
                                            <option value="yes" <?php selected( $wcpdf_hide_bundles, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $wcpdf_hide_bundles, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[compatible_wcpdf_hide_bundled]">
                                            <option value="yes" <?php selected( $wcpdf_hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $wcpdf_hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_compatible">
                                    <th colspan="2">
										<?php esc_html_e( 'WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels', 'woo-product-bundle' ); ?>
                                        <a href="https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce/"
                                           target="_blank"><span class="dashicons dashicons-external"></span></a>
                                    </th>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundles', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[compatible_pklist_hide_bundles]">
                                            <option value="yes" <?php selected( $pklist_hide_bundles, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no"<?php selected( $pklist_hide_bundles, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <select name="woosb_settings[compatible_pklist_hide_bundled]">
                                            <option value="yes" <?php selected( $pklist_hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                            <option value="no" <?php selected( $pklist_hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="submit show_if_section_all">
                                    <th colspan="2">
										<?php settings_fields( 'woosb_settings' ); ?>
										<?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab === 'localization' ) { ?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading">
                                    <th scope="row"><?php esc_html_e( 'General', 'woo-product-bundle' ); ?></th>
                                    <td>
										<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'woo-product-bundle' ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total text', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[total]" class="regular-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'total' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Bundle price:', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Saved text', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[saved]" class="regular-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'saved' ) ); ?>"
                                               placeholder="<?php esc_attr_e( '(saved [d])', 'woo-product-bundle' ); ?>"/>
                                        <span class="description"><?php esc_html_e( 'Use [d] to show the saved percentage or amount.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Choose an attribute', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[choose]" class="regular-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'choose' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Choose %s', 'woo-product-bundle' ); ?>"/>
                                        <span class="description"><?php esc_html_e( 'Use %s to show the attribute name.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Clear', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[clear]" class="regular-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'clear' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Clear', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( '"Add to cart" button labels', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Shop/archive page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <div style="margin-bottom: 5px">
                                            <input type="text" class="regular-text"
                                                   name="woosb_localization[button_add]"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'button_add' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Add to cart', 'woo-product-bundle' ); ?>"/>
                                            <span class="description"><?php esc_html_e( 'For purchasable bundle.', 'woo-product-bundle' ); ?></span>
                                        </div>
                                        <div style="margin-bottom: 5px">
                                            <input type="text" class="regular-text"
                                                   name="woosb_localization[button_select]"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'button_select' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Select options', 'woo-product-bundle' ); ?>"/>
                                            <span class="description"><?php esc_html_e( 'For purchasable bundle and has variable product(s).', 'woo-product-bundle' ); ?></span>
                                        </div>
                                        <div>
                                            <input type="text" class="regular-text"
                                                   name="woosb_localization[button_read]"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'button_read' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Read more', 'woo-product-bundle' ); ?>"/>
                                            <span class="description"><?php esc_html_e( 'For un-purchasable bundle.', 'woo-product-bundle' ); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Single product page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[button_single]" class="regular-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'button_single' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Add to cart', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Alert', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Require selection', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_selection]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_selection' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Please select a purchasable variation for [name] before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Enforce a selection', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_empty]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_empty' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Please choose at least one product before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Minimum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_min]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_min' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Please choose at least a total quantity of [min] products before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Maximum reached', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_max]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_max' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'Sorry, you can only choose at max a total quantity of [max] products before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total minimum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_total_min]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_total_min' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'The total must meet the minimum amount of [min].', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total maximum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <input type="text" name="woosb_localization[alert_total_max]" class="large-text"
                                               value="<?php echo esc_attr( WPCleverWoosb_Helper::localization( 'alert_total_max' ) ); ?>"
                                               placeholder="<?php esc_attr_e( 'The total must meet the maximum amount of [max].', 'woo-product-bundle' ); ?>"/>
                                    </td>
                                </tr>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'woosb_localization' ); ?>
										<?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab === 'premium' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>
                                Get the Premium Version just $29! <a
                                        href="https://wpclever.net/downloads/product-bundles?utm_source=pro&utm_medium=woosb&utm_campaign=wporg"
                                        target="_blank">https://wpclever.net/downloads/product-bundles</a>
                            </p>
                            <p><strong>Extra features for Premium Version:</strong></p>
                            <ul style="margin-bottom: 0">
                                <li>- Add a variable product or a specific variation to a bundle.</li>
                                <li>- Get the lifetime update & premium support.</li>
                            </ul>
                        </div>
					<?php } ?>
                </div>
            </div>
			<?php
		}

		function search_settings() {
			$search_sku      = WPCleverWoosb_Helper::get_setting( 'search_sku', 'no' );
			$search_id       = WPCleverWoosb_Helper::get_setting( 'search_id', 'no' );
			$search_exact    = WPCleverWoosb_Helper::get_setting( 'search_exact', 'no' );
			$search_sentence = WPCleverWoosb_Helper::get_setting( 'search_sentence', 'no' );
			$search_same     = WPCleverWoosb_Helper::get_setting( 'search_same', 'no' );
			?>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search limit', 'woo-product-bundle' ); ?></th>
                <td>
                    <input type="number" min="1" max="500" class="woosb_search_limit"
                           name="woosb_settings[search_limit]"
                           value="<?php echo WPCleverWoosb_Helper::get_setting( 'search_limit', 10 ); ?>"/>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search by SKU', 'woo-product-bundle' ); ?></th>
                <td>
                    <select name="woosb_settings[search_sku]" class="woosb_search_sku">
                        <option value="yes" <?php selected( $search_sku, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                        <option value="no" <?php selected( $search_sku, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search by ID', 'woo-product-bundle' ); ?></th>
                <td>
                    <select name="woosb_settings[search_id]" class="woosb_search_id">
                        <option value="yes" <?php selected( $search_id, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                        <option value="no" <?php selected( $search_id, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'Search by ID when entering the numeric only.', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search exact', 'woo-product-bundle' ); ?></th>
                <td>
                    <select name="woosb_settings[search_exact]" class="woosb_search_exact">
                        <option value="yes" <?php selected( $search_exact, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                        <option value="no" <?php selected( $search_exact, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'Match whole product title or content?', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search sentence', 'woo-product-bundle' ); ?></th>
                <td>
                    <select name="woosb_settings[search_sentence]" class="woosb_search_sentence">
                        <option value="yes" <?php selected( $search_sentence, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                        <option value="no" <?php selected( $search_sentence, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'Do a phrase search?', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Accept same products', 'woo-product-bundle' ); ?></th>
                <td>
                    <select name="woosb_settings[search_same]" class="woosb_search_same">
                        <option value="yes" <?php selected( $search_same, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                        <option value="no" <?php selected( $search_same, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'If yes, a product can be added many times.', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Product types', 'woo-product-bundle' ); ?></th>
                <td>
					<?php
					$search_types  = WPCleverWoosb_Helper::get_setting( 'search_types', array( 'all' ) );
					$product_types = wc_get_product_types();
					$product_types = array_merge( array( 'all' => esc_html__( 'All', 'woo-product-bundle' ) ), $product_types );
					$key_pos       = array_search( 'variable', array_keys( $product_types ) );

					if ( $key_pos !== false ) {
						$key_pos ++;
						$second_array  = array_splice( $product_types, $key_pos );
						$product_types = array_merge( $product_types, array( 'variation' => esc_html__( ' → Variation', 'woo-product-bundle' ) ), $second_array );
					}

					echo '<select name="woosb_settings[search_types][]" class="woosb_search_types" multiple style="width: 200px; height: 150px;">';

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
			wp_enqueue_style( 'woosb-frontend', WOOSB_URI . 'assets/css/frontend.css', array(), WOOSB_VERSION );
			wp_enqueue_script( 'woosb-frontend', WOOSB_URI . 'assets/js/frontend.js', array( 'jquery' ), WOOSB_VERSION, true );
			wp_localize_script( 'woosb-frontend', 'woosb_vars', array(
					'price_decimals'           => wc_get_price_decimals(),
					'price_format'             => get_woocommerce_price_format(),
					'price_thousand_separator' => wc_get_price_thousand_separator(),
					'price_decimal_separator'  => wc_get_price_decimal_separator(),
					'currency_symbol'          => get_woocommerce_currency_symbol(),
					'trim_zeros'               => apply_filters( 'woosb_price_trim_zeros', apply_filters( 'woocommerce_price_trim_zeros', false ) ),
					'change_image'             => WPCleverWoosb_Helper::get_setting( 'change_image', 'yes' ),
					'bundled_price'            => WPCleverWoosb_Helper::get_setting( 'bundled_price', 'price' ),
					'bundled_price_from'       => WPCleverWoosb_Helper::get_setting( 'bundled_price_from', 'sale_price' ),
					'change_price'             => WPCleverWoosb_Helper::get_setting( 'change_price', 'yes' ),
					'price_selector'           => WPCleverWoosb_Helper::get_setting( 'change_price_custom', '' ),
					'saved_text'               => WPCleverWoosb_Helper::localization( 'saved', esc_html__( '(saved [d])', 'woo-product-bundle' ) ),
					'price_text'               => WPCleverWoosb_Helper::localization( 'total', esc_html__( 'Bundle price:', 'woo-product-bundle' ) ),
					'alert_selection'          => WPCleverWoosb_Helper::localization( 'alert_selection', esc_html__( 'Please select a purchasable variation for [name] before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_empty'              => WPCleverWoosb_Helper::localization( 'alert_empty', esc_html__( 'Please choose at least one product before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_min'                => WPCleverWoosb_Helper::localization( 'alert_min', esc_html__( 'Please choose at least a total quantity of [min] products before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_max'                => WPCleverWoosb_Helper::localization( 'alert_max', esc_html__( 'Sorry, you can only choose at max a total quantity of [max] products before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_total_min'          => WPCleverWoosb_Helper::localization( 'alert_total_min', esc_html__( 'The total must meet the minimum amount of [min].', 'woo-product-bundle' ) ),
					'alert_total_max'          => WPCleverWoosb_Helper::localization( 'alert_total_max', esc_html__( 'The total must meet the maximum amount of [max].', 'woo-product-bundle' ) ),
				)
			);
		}

		function admin_enqueue_scripts() {
			wp_enqueue_style( 'hint', WOOSB_URI . 'assets/css/hint.css' );
			wp_enqueue_style( 'woosb-backend', WOOSB_URI . 'assets/css/backend.css', array(), WOOSB_VERSION );
			wp_enqueue_script( 'woosb-backend', WOOSB_URI . 'assets/js/backend.js', array(
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-sortable'
			), WOOSB_VERSION, true );
			wp_localize_script( 'woosb-backend', 'woosb_vars', array(
					'use_sku'                  => apply_filters( 'woosb_use_sku', false ),
					'price_decimals'           => wc_get_price_decimals(),
					'price_thousand_separator' => wc_get_price_thousand_separator(),
					'price_decimal_separator'  => wc_get_price_decimal_separator()
				)
			);
		}

		function action_links( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WOOSB_FILE );
			}

			if ( $plugin === $file ) {
				$settings = '<a href="' . admin_url( 'admin.php?page=wpclever-woosb&tab=settings' ) . '">' . esc_html__( 'Settings', 'woo-product-bundle' ) . '</a>';
				//$links['wpc-premium'] = '<a href="' . admin_url( 'admin.php?page=wpclever-woosb&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'woo-product-bundle' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WOOSB_FILE );
			}

			if ( $plugin === $file ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( WOOSB_DOCS ) . '" target="_blank">' . esc_html__( 'Docs', 'woo-product-bundle' ) . '</a>',
					'support' => '<a href="' . esc_url( WOOSB_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'woo-product-bundle' ) . '</a>',
				);

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		function cart_contents_count( $count ) {
			// count for cart contents
			$cart_count = WPCleverWoosb_Helper::get_setting( 'cart_contents_count', 'bundle' );

			if ( $cart_count !== 'both' ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( ( $cart_count === 'bundled_products' ) && ! empty( $cart_item['woosb_ids'] ) ) {
						$count -= $cart_item['quantity'];
					}

					if ( ( $cart_count === 'bundle' ) && ! empty( $cart_item['woosb_parent_id'] ) ) {
						$count -= $cart_item['quantity'];
					}
				}
			}

			return apply_filters( 'woosb_cart_contents_count', $count );
		}

		function get_item_count( $count, $type, $order ) {
			// count for order items
			$cart_count    = WPCleverWoosb_Helper::get_setting( 'cart_contents_count', 'bundle' );
			$order_bundles = $order_bundled = 0;

			if ( $cart_count !== 'both' ) {
				$order_items = $order->get_items( 'line_item' );

				foreach ( $order_items as $order_item ) {
					if ( $order_item->get_meta( '_woosb_parent_id' ) ) {
						$order_bundled += $order_item->get_quantity();
					}

					if ( $order_item->get_meta( '_woosb_ids' ) ) {
						$order_bundles += $order_item->get_quantity();
					}
				}

				if ( ( $cart_count === 'bundled_products' ) && ( $order_bundled > 0 ) ) {
					return $count - $order_bundles;
				}

				if ( ( $cart_count === 'bundle' ) && ( $order_bundles > 0 ) ) {
					return $count - $order_bundled;
				}
			}

			return apply_filters( 'woosb_get_item_count', $count );
		}

		function cart_item_name( $name, $cart_item ) {
			if ( ! empty( $cart_item['woosb_parent_id'] ) && ( WPCleverWoosb_Helper::get_setting( 'hide_bundle_name', 'no' ) === 'no' ) ) {
				$parent_id = apply_filters( 'woosb_item_id', $cart_item['woosb_parent_id'] );

				if ( ( strpos( $name, '</a>' ) !== false ) && ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
					return '<a href="' . get_permalink( $parent_id ) . '">' . get_the_title( $parent_id ) . '</a> &rarr; ' . $name;
				}

				return get_the_title( $parent_id ) . ' &rarr; ' . strip_tags( $name );
			}

			return $name;
		}

		function cart_item_removed( $cart_item_key, $cart ) {
			$new_keys = [];

			foreach ( $cart->cart_contents as $cart_key => $cart_item ) {
				if ( ! empty( $cart_item['woosb_key'] ) ) {
					$new_keys[ $cart_key ] = $cart_item['woosb_key'];
				}
			}

			if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['woosb_keys'] ) ) {
				$keys = $cart->removed_cart_contents[ $cart_item_key ]['woosb_keys'];

				foreach ( $keys as $key ) {
					$cart->remove_cart_item( $key );

					if ( $new_key = array_search( $key, $new_keys ) ) {
						$cart->remove_cart_item( $new_key );
					}
				}
			}
		}

		function check_in_cart( $product_id ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( $cart_item['product_id'] == $product_id ) {
					return true;
				}
			}

			return false;
		}

		function found_in_cart( $found_in_cart, $product_id ) {
			if ( self::check_in_cart( $product_id ) ) {
				return true;
			}

			return $found_in_cart;
		}

		function add_to_cart_validation( $passed, $product_id ) {
			if ( isset( $_REQUEST['order_again'] ) ) {
				return $passed;
			}

			$product = wc_get_product( $product_id );

			if ( $product && $product->is_type( 'woosb' ) ) {
				if ( isset( $_REQUEST['woosb_ids'] ) ) {
					$ids = WPCleverWoosb_Helper::clean_ids( $_REQUEST['woosb_ids'] );
					$product->build_items( $ids );
				}

				if ( ( $items = $product->get_items() ) && ! empty( $items ) ) {
					$count       = $total = 0;
					$items       = WPCleverWoosb_Helper::minify_items( $items );
					$qty         = isset( $_REQUEST['quantity'] ) ? (int) $_REQUEST['quantity'] : 1;
					$min_each    = (float) ( get_post_meta( $product_id, 'woosb_limit_each_min', true ) ?: 0 );
					$max_each    = (float) ( get_post_meta( $product_id, 'woosb_limit_each_max', true ) ?: - 1 );
					$min_whole   = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 1 );
					$max_whole   = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ?: - 1 );
					$total_min   = (float) ( get_post_meta( $product_id, 'woosb_total_limits_min', true ) ?: 0 );
					$total_max   = (float) ( get_post_meta( $product_id, 'woosb_total_limits_max', true ) ?: - 1 );
					$check_total = ! $product->is_fixed_price() && ( $product->is_optional() || $product->has_variables() ) && ( get_post_meta( $product_id, 'woosb_total_limits', true ) === 'on' );

					foreach ( $items as $item ) {
						$_id      = $item['id'];
						$_qty     = $item['qty'];
						$_product = wc_get_product( $_id );
						$count    += $_qty;

						if ( $check_total ) {
							$total += wc_get_price_to_display( $_product, array( 'qty' => $_qty ) );
						}

						if ( $product->is_optional() && ( ( $min_each > 0 && $_qty < $min_each ) || ( $max_each > 0 && $_qty > $max_each ) ) ) {
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( ! $_product ) {
							wc_add_notice( esc_html__( 'One of the bundled products is unavailable.', 'woo-product-bundle' ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( $_product->is_type( 'variable' ) || $_product->is_type( 'woosb' ) ) {
							wc_add_notice( sprintf( esc_html__( '"%s" is un-purchasable.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( ! $_product->is_in_stock() || ! $_product->is_purchasable() ) {
							wc_add_notice( sprintf( esc_html__( '"%s" is un-purchasable.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( ! $_product->has_enough_stock( $_qty * $qty ) ) {
							wc_add_notice( sprintf( esc_html__( '"%s" has not enough stock.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( $_product->is_sold_individually() && self::check_in_cart( $_id ) ) {
							wc_add_notice( sprintf( esc_html__( 'You cannot add another "%s" to the cart.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( $_product->managing_stock() ) {
							$products_qty_in_cart = WC()->cart->get_cart_item_quantities();

							if ( isset( $products_qty_in_cart[ $_product->get_stock_managed_by_id() ] ) && ! $_product->has_enough_stock( $products_qty_in_cart[ $_product->get_stock_managed_by_id() ] + $_qty * $qty ) ) {
								wc_add_notice( sprintf( esc_html__( '"%s" has not enough stock.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}
						}

						if ( post_password_required( $_id ) ) {
							wc_add_notice( sprintf( esc_html__( '"%s" is protected and cannot be purchased.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}
					}

					if ( $product->is_optional() && ( ( $min_whole > 0 && $count < $min_whole ) || ( $max_whole > 0 && $count > $max_whole ) ) ) {
						wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

						return false;
					}

					if ( $check_total ) {
						if ( $discount_amount = $product->get_discount_amount() ) {
							$total -= $discount_amount;
						} elseif ( $discount_percentage = $product->get_discount_percentage() ) {
							$total = $total * ( 100 - $discount_percentage ) / 100;
						}

						if ( $total_min > 0 && $total < $total_min ) {
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( $total_max > 0 && $total > $total_max ) {
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}
					}
				} else {
					wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

					return false;
				}
			}

			return $passed;
		}

		function add_cart_item_data( $cart_item_data, $product_id ) {
			$_product = wc_get_product( $product_id );

			if ( $_product && $_product->is_type( 'woosb' ) && ( $ids = $_product->get_ids() ) ) {
				// make sure that is bundle
				if ( isset( $_REQUEST['woosb_ids'] ) ) {
					$ids = WPCleverWoosb_Helper::clean_ids( $_REQUEST['woosb_ids'] );
					unset( $_REQUEST['woosb_ids'] );
				}

				if ( ! empty( $ids ) ) {
					$cart_item_data['woosb_ids'] = $ids;
				}
			}

			return $cart_item_data;
		}

		function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
			if ( ! empty( $cart_item_data['woosb_ids'] ) && isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
				WC()->cart->cart_contents[ $cart_item_key ]['data']->build_items( $cart_item_data['woosb_ids'] );
				$items = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_items();
				self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
			}
		}

		function restore_cart_item( $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_ids'] ) ) {
				WC()->cart->cart_contents[ $cart_item_key ]['data']->build_items( WC()->cart->cart_contents[ $cart_item_key ]['woosb_ids'] );
				unset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] );

				$product_id = WC()->cart->cart_contents[ $cart_item_key ]['product_id'];
				$quantity   = WC()->cart->cart_contents[ $cart_item_key ]['quantity'];
				$items      = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_items();

				self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
			}
		}

		function add_to_cart_items( $items, $cart_item_key, $product_id, $quantity ) {
			if ( apply_filters( 'woosb_exclude_bundled', false ) ) {
				return;
			}

			$items = WPCleverWoosb_Helper::minify_items( $items );

			$fixed_price           = WC()->cart->cart_contents[ $cart_item_key ]['data']->is_fixed_price();
			$discount_amount       = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_discount_amount();
			$discount_percentage   = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_discount_percentage();
			$exclude_unpurchasable = WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes';

			// save current key associated with woosb_parent_key
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_key']             = $cart_item_key;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_fixed_price']     = $fixed_price;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_discount_amount'] = $discount_amount;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_discount']        = $discount_percentage;

			if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
				foreach ( $items as $item ) {
					$_id        = $item['id'];
					$_qty       = $item['qty'];
					$_variation = $item['attrs'];

					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || ( $_qty <= 0 ) || in_array( $_product->get_type(), self::$types, true ) ) {
						continue;
					}

					if ( ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) && $exclude_unpurchasable ) {
						// exclude unpurchasable
						continue;
					}

					$_variation_id = 0;
					$_price        = (float) WPCleverWoosb_Helper::get_price( $_product );

					if ( $_product instanceof WC_Product_Variation ) {
						// ensure we don't add a variation to the cart directly by variation ID
						$_variation_id = $_id;
						$_id           = $_product->get_parent_id();

						if ( empty( $_variation ) ) {
							$_variation = $_product->get_variation_attributes();
						}
					}

					if ( ! $fixed_price && $discount_percentage ) {
						$_price *= ( 100 - (float) $discount_percentage ) / 100;
						$_price = round( $_price, wc_get_price_decimals() );
						$_price = apply_filters( 'woosb_item_price_add_to_cart', $_price, $item );
					}

					// add to cart
					$_data = array(
						'woosb_qty'             => $_qty,
						'woosb_price'           => $_price,
						'woosb_parent_id'       => $product_id,
						'woosb_parent_key'      => $cart_item_key,
						'woosb_fixed_price'     => $fixed_price,
						'woosb_discount_amount' => $discount_amount,
						'woosb_discount'        => $discount_percentage
					);

					$_key = WC()->cart->add_to_cart( $_id, $_qty * $quantity, $_variation_id, $_variation, $_data );

					if ( empty( $_key ) ) {
						if ( ! $exclude_unpurchasable ) {
							// can't add the bundled product
							if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] ) ) {
								$keys = WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'];

								foreach ( $keys as $key ) {
									// remove all bundled products
									WC()->cart->remove_cart_item( $key );
								}

								// remove the bundle
								WC()->cart->remove_cart_item( $cart_item_key );

								// break out of the loop
								break;
							}
						}
					} elseif ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] ) || ! in_array( $_key, WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'], true ) ) {
						// save current key
						WC()->cart->cart_contents[ $_key ]['woosb_key'] = $_key;

						// add keys for parent
						WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'][] = $_key;
					}
				} // end foreach
			}
		}

		function get_cart_item_from_session( $cart_item, $session_values ) {
			if ( ! empty( $session_values['woosb_ids'] ) ) {
				$cart_item['woosb_ids'] = $session_values['woosb_ids'];
			}

			if ( ! empty( $session_values['woosb_parent_id'] ) ) {
				$cart_item['woosb_parent_id']  = $session_values['woosb_parent_id'];
				$cart_item['woosb_parent_key'] = $session_values['woosb_parent_key'];
				$cart_item['woosb_qty']        = $session_values['woosb_qty'];
			}

			return $cart_item;
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
				if ( ! empty( $cart_item['woosb_key'] ) ) {
					$new_keys[ $cart_item_key ] = $cart_item['woosb_key'];
				}
			}

			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				// bundled products
				if ( ! empty( $cart_item['woosb_parent_key'] ) ) {
					$parent_new_key = array_search( $cart_item['woosb_parent_key'], $new_keys );

					// remove orphaned bundled products
					if ( ! $parent_new_key || ! isset( $cart_contents[ $parent_new_key ] ) || ( isset( $cart_contents[ $parent_new_key ]['woosb_keys'] ) && ! in_array( $cart_item_key, $cart_contents[ $parent_new_key ]['woosb_keys'] ) ) ) {
						unset( $cart_contents[ $cart_item_key ] );
						continue;
					}

					// sync quantity
					if ( ! empty( $cart_item['woosb_qty'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['woosb_qty'] * $cart_contents[ $parent_new_key ]['quantity'];
					}

					// set price
					if ( isset( $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
						$cart_item['data']->set_price( 0 );
					} elseif ( isset( $cart_item['woosb_price'], $cart_item['woosb_discount_amount'], $cart_item['woosb_discount'] ) && ( $cart_item['woosb_discount_amount'] || $cart_item['woosb_discount'] ) ) {
						$cart_item['data']->set_price( $cart_item['woosb_price'] );
					}
				}

				// bundles
				if ( ! empty( $cart_item['woosb_ids'] ) && isset( $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
					// set tax status 'none'
					$cart_item['data']->set_tax_status( 'none' );

					// set price zero, calculate later
					if ( isset( $cart_item['woosb_discount_amount'] ) && $cart_item['woosb_discount_amount'] ) {
						$cart_item['data']->set_price( - (float) $cart_item['woosb_discount_amount'] );
					} else {
						$cart_item['data']->set_price( 0 );
					}

					if ( ! empty( $cart_item['woosb_keys'] ) ) {
						$bundles_price = 0;

						foreach ( $cart_item['woosb_keys'] as $key ) {
							if ( isset( $cart_contents[ $key ], $cart_contents[ $key ]['data'] ) ) {
								if ( WC()->cart->display_prices_including_tax() ) {
									$_price = wc_get_price_including_tax( $cart_contents[ $key ]['data'], array(
										'price' => $cart_contents[ $key ]['woosb_price'],
										'qty'   => $cart_contents[ $key ]['woosb_qty']
									) );
								} else {
									$_price = wc_get_price_excluding_tax( $cart_contents[ $key ]['data'], array(
										'price' => $cart_contents[ $key ]['woosb_price'],
										'qty'   => $cart_contents[ $key ]['woosb_qty']
									) );
								}

								$bundles_price += round( $_price, wc_get_price_decimals() );
							}
						}

						if ( ! empty( $cart_item['woosb_discount_amount'] ) ) {
							$bundles_price -= (float) $cart_item['woosb_discount_amount'];
						}

						if ( $cart_item['quantity'] > 0 ) {
							WC()->cart->cart_contents[ $cart_item_key ]['woosb_price'] = round( $bundles_price, wc_get_price_decimals() );
						}
					}
				}
			}
		}

		function cart_item_price( $price, $cart_item ) {
			if ( isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
				$price = wc_price( $cart_item['woosb_price'] );
			}

			if ( isset( $cart_item['woosb_parent_id'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
				if ( $cart_item['data']->is_taxable() ) {
					if ( WC()->cart->display_prices_including_tax() ) {
						$price = wc_price( wc_get_price_including_tax( $cart_item['data'], array(
							'price' => $cart_item['woosb_price']
						) ) );
					} else {
						$price = wc_price( wc_get_price_excluding_tax( $cart_item['data'], array(
							'price' => $cart_item['woosb_price']
						) ) );
					}
				} else {
					$price = wc_price( $cart_item['woosb_price'] );
				}
			}

			return $price;
		}

		function cart_item_subtotal( $subtotal, $cart_item = null ) {
			$new_subtotal = false;

			if ( isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
				$new_subtotal = true;
				$subtotal     = wc_price( $cart_item['woosb_price'] * $cart_item['quantity'] );
			}

			if ( isset( $cart_item['woosb_parent_id'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
				$new_subtotal = true;

				if ( $cart_item['data']->is_taxable() ) {
					if ( WC()->cart->display_prices_including_tax() ) {
						$subtotal = wc_price( wc_get_price_including_tax( $cart_item['data'], array(
							'price' => $cart_item['woosb_price'],
							'qty'   => $cart_item['woosb_qty']
						) ) );
					} else {
						$subtotal = wc_price( wc_get_price_excluding_tax( $cart_item['data'], array(
							'price' => $cart_item['woosb_price'],
							'qty'   => $cart_item['woosb_qty']
						) ) );
					}
				} else {
					$subtotal = wc_price( $cart_item['woosb_price'] * $cart_item['quantity'] );
				}
			}

			if ( $new_subtotal && ( $cart_product = $cart_item['data'] ) ) {
				if ( $cart_product->is_taxable() ) {
					if ( WC()->cart->display_prices_including_tax() ) {
						$subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					} else {
						$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				}
			}

			return $subtotal;
		}

		function mini_cart_item_visible( $visible, $cart_item ) {
			if ( ! apply_filters( 'woosb_item_visible', true, $cart_item['data'] ) ) {
				return false;
			}

			if ( isset( $cart_item['woosb_parent_id'] ) && ( WPCleverWoosb_Helper::get_setting( 'hide_bundled_mini_cart', 'no' ) === 'yes' ) ) {
				return false;
			}

			return $visible;
		}

		function cart_item_visible( $visible, $cart_item ) {
			if ( ! apply_filters( 'woosb_item_visible', true, $cart_item['data'] ) ) {
				return false;
			}

			if ( isset( $cart_item['woosb_parent_id'] ) && ( WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' ) !== 'no' ) ) {
				return false;
			}

			return $visible;
		}

		function order_item_visible( $visible, $order_item ) {
			if ( ! apply_filters( 'woosb_item_visible', true, $order_item->get_product() ) ) {
				return false;
			}

			if ( $order_item->get_meta( '_woosb_parent_id' ) && ( WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' ) !== 'no' ) ) {
				return false;
			}

			return $visible;
		}

		function cart_item_class( $class, $cart_item ) {
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				$class .= ' woosb-cart-item woosb-cart-child woosb-item-child';
			} elseif ( isset( $cart_item['woosb_ids'] ) ) {
				$class .= ' woosb-cart-item woosb-cart-parent woosb-item-parent';
			}

			return $class;
		}

		function get_item_data( $data, $cart_item ) {
			if ( empty( $cart_item['woosb_ids'] ) ) {
				return $data;
			}

			$cart_item['data']->build_items( $cart_item['woosb_ids'] );
			$items     = $cart_item['data']->get_items();
			$items_str = array();

			if ( is_array( $items ) && ! empty( $items ) ) {
				foreach ( $items as $item ) {
					if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $cart_item['data'] ) ) {
						continue;
					}

					$items_str[] = apply_filters( 'woosb_order_bundled_product_name', $item['qty'] . ' × ' . get_the_title( $item['id'] ), $item );
				}
			}

			$data['woosb_data'] = array(
				'key'     => esc_html__( 'Bundled products', 'woo-product-bundle' ),
				'value'   => esc_html( $cart_item['woosb_ids'] ),
				'display' => apply_filters( 'woosb_order_bundled_product_names', implode( '; ', $items_str ), $items ),
			);

			return $data;
		}

		function create_order_line_item( $order_item, $cart_item_key, $values ) {
			if ( isset( $values['woosb_parent_id'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_parent_id', $values['woosb_parent_id'] );
			}

			if ( isset( $values['woosb_ids'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_ids', $values['woosb_ids'] );
			}

			if ( isset( $values['woosb_price'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_price', $values['woosb_price'] );
			}
		}

		function ajax_add_order_item_meta( $order_item_id, $order_item, $order ) {
			$quantity = $order_item->get_quantity();

			if ( 'line_item' === $order_item->get_type() ) {
				$product = $order_item->get_product();

				if ( $product && $product->is_type( 'woosb' ) && ( $items = $product->get_items() ) ) {
					$product_id = $product->get_id();
					$items      = WPCleverWoosb_Helper::minify_items( $items );

					// get bundle info
					$fixed_price         = $product->is_fixed_price();
					$discount_amount     = $product->get_discount_amount();
					$discount_percentage = $product->get_discount_percentage();

					// add the bundle
					if ( ! $fixed_price ) {
						if ( $discount_amount ) {
							$product->set_price( - (float) $discount_amount );
						} else {
							$product->set_price( 0 );
						}
					}

					if ( $order_id = $order->add_product( $product, $quantity ) ) {
						$order_item = $order->get_item( $order_id );
						$order_item->update_meta_data( '_woosb_ids', $product->get_ids(), true );
						$order_item->save();

						foreach ( $items as $item ) {
							$_product = wc_get_product( $item['id'] );

							if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) ) {
								continue;
							}

							if ( $fixed_price ) {
								$_product->set_price( 0 );
							} elseif ( $discount_percentage ) {
								$_price = (float) ( 100 - $discount_percentage ) * WPCleverWoosb_Helper::get_price( $_product ) / 100;
								$_product->set_price( $_price );
							}

							// add bundled products
							$_order_item_id = $order->add_product( $_product, $item['qty'] * $quantity );

							if ( ! $_order_item_id ) {
								continue;
							}

							$_order_item = $order->get_item( $_order_item_id );
							$_order_item->update_meta_data( '_woosb_parent_id', $product_id, true );
							$_order_item->save();
						}

						// remove the old bundle
						$order->remove_item( $order_item_id );
					}
				}

				$order->save();
			}
		}

		function hidden_order_itemmeta( $hidden ) {
			return array_merge( $hidden, array(
				'_woosb_parent_id',
				'_woosb_ids',
				'_woosb_price',
				'woosb_parent_id',
				'woosb_ids',
				'woosb_price'
			) );
		}

		function before_order_itemmeta( $order_item_id, $order_item ) {
			if ( ( $ids = $order_item->get_meta( '_woosb_ids' ) ) && ( is_admin() || ( WPCleverWoosb_Helper::get_setting( 'hide_bundled', 'no' ) === 'yes_text' ) ) ) {
				$items     = self::get_bundled( $ids );
				$items_str = array();

				if ( is_array( $items ) && ! empty( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! apply_filters( 'woosb_item_visible', true, $item['id'] ) ) {
							continue;
						}

						$items_str[] = apply_filters( 'woosb_order_bundled_product_name', $item['qty'] . ' × ' . get_the_title( $item['id'] ), $item );
					}
				}

				$items_str = apply_filters( 'woosb_order_bundled_product_names', implode( '; ', $items_str ), $items );
				echo apply_filters( 'woosb_before_order_itemmeta_bundles', '<div class="woosb-itemmeta-bundles">' . sprintf( esc_html__( 'Bundled products: %s', 'woo-product-bundle' ), $items_str ) . '</div>', $order_item_id, $order_item );
			}

			if ( is_admin() && ( $parent_id = $order_item->get_meta( '_woosb_parent_id' ) ) ) {
				echo apply_filters( 'woosb_before_order_itemmeta_bundled', '<div class="woosb-itemmeta-bundled">' . sprintf( esc_html__( 'Bundled in: %s', 'woo-product-bundle' ), get_the_title( $parent_id ) ) . '</div>', $order_item_id, $order_item );
			}
		}

		function formatted_line_subtotal( $subtotal, $order_item ) {
			if ( isset( $order_item['_woosb_ids'], $order_item['_woosb_price'] ) ) {
				return wc_price( $order_item['_woosb_price'] * $order_item['quantity'] );
			}

			return $subtotal;
		}

		function cart_item_remove_link( $link, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_parent_key'] ) ) {
				$parent_key = WC()->cart->cart_contents[ $cart_item_key ]['woosb_parent_key'];

				if ( isset( WC()->cart->cart_contents[ $parent_key ] ) || array_search( $parent_key, array_column( WC()->cart->cart_contents, 'woosb_key', 'key' ) ) ) {
					return '';
				}
			}

			return $link;
		}

		function cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
			// add qty as text - not input
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				return $cart_item['quantity'];
			}

			return $quantity;
		}

		function update_search_settings() {
			$settings                    = (array) get_option( 'woosb_settings', [] );
			$settings['search_limit']    = (int) sanitize_text_field( $_POST['limit'] );
			$settings['search_sku']      = sanitize_text_field( $_POST['sku'] );
			$settings['search_id']       = sanitize_text_field( $_POST['id'] );
			$settings['search_exact']    = sanitize_text_field( $_POST['exact'] );
			$settings['search_sentence'] = sanitize_text_field( $_POST['sentence'] );
			$settings['search_same']     = sanitize_text_field( $_POST['same'] );
			$settings['search_types']    = array_map( 'sanitize_text_field', (array) $_POST['types'] );

			update_option( 'woosb_settings', $settings );
			die();
		}

		function get_search_results() {
			$keyword   = sanitize_text_field( $_POST['keyword'] );
			$added_ids = explode( ',', WPCleverWoosb_Helper::clean_ids( $_POST['ids'] ) );
			$types     = WPCleverWoosb_Helper::get_setting( 'search_types', array( 'all' ) );

			if ( ( WPCleverWoosb_Helper::get_setting( 'search_id', 'no' ) === 'yes' ) && is_numeric( $keyword ) ) {
				// search by id
				$query_args = array(
					'p'         => absint( $keyword ),
					'post_type' => 'product'
				);
			} else {
				$query_args = array(
					'is_woosb'       => true,
					'post_type'      => 'product',
					'post_status'    => array( 'publish', 'private' ),
					's'              => $keyword,
					'posts_per_page' => WPCleverWoosb_Helper::get_setting( 'search_limit', 10 )
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

				if ( WPCleverWoosb_Helper::get_setting( 'search_same', 'no' ) !== 'yes' ) {
					$exclude_ids = array();

					if ( is_array( $added_ids ) && count( $added_ids ) > 0 ) {
						foreach ( $added_ids as $added_id ) {
							$added_id_new  = explode( '/', $added_id );
							$exclude_ids[] = absint( isset( $added_id_new[0] ) ? $added_id_new[0] : 0 );
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
					$_product = wc_get_product( get_the_ID() );

					if ( ! $_product ) {
						continue;
					}

					if ( ! $_product->is_type( 'variable' ) || in_array( 'variable', $types, true ) || in_array( 'all', $types, true ) ) {
						self::product_data_li( $_product, 1, true );
					}

					if ( $_product->is_type( 'variable' ) && ( empty( $types ) || in_array( 'all', $types, true ) || in_array( 'variation', $types, true ) ) ) {
						// show all children
						$children = $_product->get_children();

						if ( is_array( $children ) && count( $children ) > 0 ) {
							foreach ( $children as $child ) {
								$child_product = wc_get_product( $child );
								self::product_data_li( $child_product, 1, true );
							}
						}
					}
				}

				echo '</ul>';
				wp_reset_postdata();
			} else {
				echo '<ul><span>' . sprintf( esc_html__( 'No results found for "%s"', 'woo-product-bundle' ), $keyword ) . '</span></ul>';
			}

			die();
		}

		function search_sku( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				global $wpdb;

				$sku = sanitize_text_field( $query->query['s'] );
				$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value = %s;", $sku ) );

				if ( ! $ids ) {
					return;
				}

				$posts = array();

				foreach ( $ids as $id ) {
					$post = get_post( $id );

					if ( $post->post_type === 'product_variation' ) {
						$posts[] = $post->post_parent;
					} else {
						$posts[] = $post->ID;
					}
				}

				unset( $query->query['s'], $query->query_vars['s'] );
				$query->set( 'post__in', $posts );
			}
		}

		function search_exact( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				$query->set( 'exact', true );
			}
		}

		function search_sentence( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				$query->set( 'sentence', true );
			}
		}

		function product_type_selector( $types ) {
			$types['woosb'] = esc_html__( 'Smart bundle', 'woo-product-bundle' );

			return $types;
		}

		function product_data_tabs( $tabs ) {
			$tabs['woosb'] = array(
				'label'  => esc_html__( 'Bundled Products', 'woo-product-bundle' ),
				'target' => 'woosb_settings',
				'class'  => array( 'show_if_woosb' ),
			);

			return $tabs;
		}

		function product_summary_bundles() {
			self::show_bundles();
		}

		function product_summary_bundled() {
			self::show_bundled();
		}

		function product_tabs( $tabs ) {
			global $product;
			$product_id = $product->get_id();

			if ( ( WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' ) === 'tab' ) && $product->is_type( 'woosb' ) ) {
				$tabs['woosb'] = array(
					'title'    => esc_html__( 'Bundled products', 'woo-product-bundle' ),
					'priority' => 50,
					'callback' => array( $this, 'product_tab_bundled' )
				);
			}

			if ( ( WPCleverWoosb_Helper::get_setting( 'bundles_position', 'no' ) === 'tab' ) && ! $product->is_type( 'woosb' ) && self::get_bundles( $product_id ) ) {
				$tabs['woosb'] = array(
					'title'    => esc_html__( 'Bundles', 'woo-product-bundle' ),
					'priority' => 50,
					'callback' => array( $this, 'product_tab_bundles' )
				);
			}

			return $tabs;
		}

		function product_tab_bundled() {
			self::show_bundled();
		}

		function product_tab_bundles() {
			self::show_bundles();
		}

		function product_data_panels() {
			global $post;
			$post_id = $post->ID;
			$ids     = '';

			if ( get_post_meta( $post_id, 'woosb_ids', true ) ) {
				$ids = get_post_meta( $post_id, 'woosb_ids', true );
			} elseif ( isset( $_GET['woosb_ids'] ) ) {
				$ids = implode( ',', explode( '.', sanitize_text_field( $_GET['woosb_ids'] ) ) );
			}

			$ids = WPCleverWoosb_Helper::clean_ids( $ids );

			if ( isset( $_GET['woosb_ids'] ) && ! empty( $_GET['woosb_ids'] ) ) {
				?>
                <script type="text/javascript">
                  jQuery(document).ready(function($) {
                    $('#product-type').val('woosb').trigger('change');
                  });
                </script>
				<?php
			}
			?>
            <div id='woosb_settings' class='panel woocommerce_options_panel woosb_table'>
                <div id="woosb_search_settings" style="display: none"
                     data-title="<?php esc_html_e( 'Search settings', 'woo-product-bundle' ); ?>">
                    <table>
						<?php self::search_settings(); ?>
                        <tr>
                            <th></th>
                            <td>
                                <button id="woosb_search_settings_update" class="button button-primary">
									<?php esc_html_e( 'Update Options', 'woo-product-bundle' ); ?>
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
                <table>
                    <tr>
                        <th><?php esc_html_e( 'Search', 'woo-product-bundle' ); ?> (<a
                                    href="<?php echo admin_url( 'admin.php?page=wpclever-woosb&tab=settings#search' ); ?>"
                                    id="woosb_search_settings_btn"><?php esc_html_e( 'settings', 'woo-product-bundle' ); ?></a>)
                        </th>
                        <td>
                            <div class="w100">
                                <span class="loading" id="woosb_loading"
                                      style="display: none;"><?php esc_html_e( 'searching...', 'woo-product-bundle' ); ?></span>
                                <input type="search" id="woosb_keyword"
                                       placeholder="<?php esc_attr_e( 'Type any keyword to search', 'woo-product-bundle' ); ?>"/>
                                <div id="woosb_results" class="woosb_results" style="display: none;"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Selected', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div class="w100">
                                <input type="hidden" id="woosb_ids" class="woosb_ids" name="woosb_ids"
                                       value="<?php echo esc_attr( $ids ); ?>" readonly/>
                                <div id="woosb_selected" class="woosb_selected">
                                    <ul>
										<?php
										if ( ! empty( $ids ) ) {
											$items = self::get_bundled( $ids );

											if ( ! empty( $items ) ) {
												foreach ( $items as $item ) {
													$_product = wc_get_product( $item['id'] );

													if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) ) {
														continue;
													}

													self::product_data_li( $_product, $item['qty'] );
												}
											}
										}
										?>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php echo esc_html__( 'Regular price', 'woo-product-bundle' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></th>
                        <td>
                            <span id="woosb_regular_price"></span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Fixed price', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_disable_auto_price" name="woosb_disable_auto_price"
                                   type="checkbox" <?php echo( get_post_meta( $post_id, 'woosb_disable_auto_price', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_disable_auto_price"><?php esc_html_e( 'Disable auto calculate price.', 'woo-product-bundle' ); ?></label>
                            <label><?php echo sprintf( esc_html__( 'If checked, %s click here to set price %s by manually.', 'woo-product-bundle' ), '<a id="woosb_set_regular_price">', '</a>' ); ?></label>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space woosb_tr_show_if_auto_price">
                        <th><?php esc_html_e( 'Discount', 'woo-product-bundle' ); ?></th>
                        <td style="vertical-align: middle; line-height: 30px;">
                            <input id="woosb_discount" name="woosb_discount" type="number" min="0" step="0.0001"
                                   max="99.9999" style="width: 80px"
                                   value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_discount', true ) ); ?>"/> <?php esc_html_e( '% or amount', 'woo-product-bundle' ); ?>
                            <input id="woosb_discount_amount" name="woosb_discount_amount" type="number" min="0"
                                   step="0.0001" style="width: 80px"
                                   value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_discount_amount', true ) ); ?>"/> <?php echo get_woocommerce_currency_symbol(); ?>
                            . <?php esc_html_e( 'If you fill both, the amount will be used.', 'woo-product-bundle' ); ?>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Custom quantity', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_optional_products" name="woosb_optional_products"
                                   type="checkbox" <?php echo( get_post_meta( $post_id, 'woosb_optional_products', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_optional_products"><?php esc_html_e( 'Allow the customer can change the quantity of each product.', 'woo-product-bundle' ); ?></label>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space woosb_tr_show_if_optional_products">
                        <th><?php esc_html_e( 'Each item\'s quantity limit', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_limit_each_min_default" name="woosb_limit_each_min_default"
                                   type="checkbox" <?php echo( get_post_meta( $post_id, 'woosb_limit_each_min_default', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_limit_each_min_default"><?php esc_html_e( 'Use default quantity as min?', 'woo-product-bundle' ); ?></label>
                            <u>or</u>
                            Min <input name="woosb_limit_each_min" type="number" min="0"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_limit_each_min', true ) ); ?>"/>
                            Max <input name="woosb_limit_each_max" type="number" min="1"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_limit_each_max', true ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space woosb_tr_show_if_optional_products">
                        <th><?php esc_html_e( 'All items\' quantity limit', 'woo-product-bundle' ); ?></th>
                        <td>
                            Min <input name="woosb_limit_whole_min" type="number" min="1"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_limit_whole_min', true ) ); ?>"/>
                            Max <input name="woosb_limit_whole_max" type="number" min="1"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_limit_whole_max', true ) ); ?>"/>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Total limits', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_total_limits" name="woosb_total_limits"
                                   type="checkbox" <?php echo( get_post_meta( $post_id, 'woosb_total_limits', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_total_limits"><?php esc_html_e( 'Configure total limits for the current bundle.', 'woo-product-bundle' ); ?></label>
                            <span class="woocommerce-help-tip"
                                  data-tip="<?php esc_attr_e( 'When a bundle includes variable products or has the Custom quantity option enabled, bundle\'s price will vary depending on the item selection. Thus, this option can be enabled to limit the bundle total\'s min-max.', 'woo-product-bundle' ); ?>"></span>
                            <span class="woosb_show_if_total_limits">
                                Min <input id="woosb_total_limits_min" name="woosb_total_limits_min" type="number"
                                           min="0" style="width: 80px"
                                           value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_total_limits_min', true ) ); ?>"/>
                                Max <input id="woosb_total_limits_max" name="woosb_total_limits_max" type="number"
                                           min="0" style="width: 80px"
                                           value="<?php echo esc_attr( get_post_meta( $post_id, 'woosb_total_limits_max', true ) ); ?>"/> <?php echo get_woocommerce_currency_symbol(); ?>
                            </span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Shipping fee', 'woo-product-bundle' ); ?></th>
                        <td style="font-style: italic">
							<?php $shipping_fee = get_post_meta( $post_id, 'woosb_shipping_fee', true ); ?>
                            <select id="woosb_shipping_fee" name="woosb_shipping_fee">
                                <option value="whole" <?php selected( $shipping_fee, 'whole' ); ?>><?php esc_html_e( 'Apply to the whole bundle', 'woo-product-bundle' ); ?></option>
                                <option value="each" <?php selected( $shipping_fee, 'each' ); ?>><?php esc_html_e( 'Apply to each bundled product', 'woo-product-bundle' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Manage stock', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_manage_stock" name="woosb_manage_stock"
                                   type="checkbox" <?php echo( get_post_meta( $post_id, 'woosb_manage_stock', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_manage_stock"><?php esc_html_e( 'Enable stock management at bundle level.', 'woo-product-bundle' ); ?></label>
                            <span class="woocommerce-help-tip"
                                  data-tip="<?php esc_attr_e( 'By default, the bundle\' stock was calculated automatically from bundled products. After enabling, please press "Update" then you can change the stock settings on the "Inventory" tab.', 'woo-product-bundle' ); ?>"></span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Custom display price', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input type="text" name="woosb_custom_price"
                                   value="<?php echo stripslashes( get_post_meta( $post_id, 'woosb_custom_price', true ) ); ?>"/>
                            E.g: <code>From $10 to
                                $100</code>. <?php esc_html_e( 'You can use %s to show the dynamic price between your custom text.', 'woo-product-bundle' ); ?>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Above text', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div class="w100">
                                <textarea
                                        name="woosb_before_text"><?php echo stripslashes( get_post_meta( $post_id, 'woosb_before_text', true ) ); ?></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Under text', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div class="w100">
                                <textarea
                                        name="woosb_after_text"><?php echo stripslashes( get_post_meta( $post_id, 'woosb_after_text', true ) ); ?></textarea>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
			<?php
		}

		function product_data_li( $product, $qty = 1, $search = false ) {
			$product_id    = $product->get_id();
			$product_sku   = $product->get_sku();
			$product_class = ! $product->is_in_stock() ? 'out-of-stock' : '';

			if ( in_array( $product->get_type(), self::$types, true ) ) {
				$product_class .= ' disabled';
			}

			if ( class_exists( 'WPCleverWoopq' ) && ( get_option( '_woopq_decimal', 'no' ) === 'yes' ) ) {
				$step = '0.000001';
			} else {
				$step = 1;
			}

			if ( $product->is_sold_individually() ) {
				$qty_input = '<input type="number" value="' . esc_attr( $qty ) . '" min="0" step="' . esc_attr( $step ) . '" max="1"/>';
			} else {
				$qty_input = '<input type="number" value="' . esc_attr( $qty ) . '" min="0" step="' . esc_attr( $step ) . '"/>';
			}

			$price     = WPCleverWoosb_Helper::get_price( $product );
			$price_max = WPCleverWoosb_Helper::get_price( $product, 'max' );

			if ( $search ) {
				$remove_btn = '<span class="woosb-remove hint--left" aria-label="' . esc_html__( 'Add', 'woo-product-bundle' ) . '">+</span>';
			} else {
				$remove_btn = '<span class="woosb-remove hint--left" aria-label="' . esc_html__( 'Remove', 'woo-product-bundle' ) . '">×</span>';
			}

			// apply filter same as frontend
			$product_name = apply_filters( 'woosb_item_product_name', $product->get_name(), $product );

			if ( $product->is_type( 'variation' ) ) {
				$edit_link = get_edit_post_link( $product->get_parent_id() );
			} else {
				$edit_link = get_edit_post_link( $product_id );
			}

			// product type & id
			$product_info = apply_filters( 'woosb_item_product_info', $product->get_type() . '<br/>#' . $product_id, $product );

			echo '<li class="' . esc_attr( trim( $product_class ) ) . '" data-sku="' . esc_attr( $product_sku ) . '" data-id="' . esc_attr( $product_id ) . '" data-price="' . esc_attr( $price ) . '" data-price-max="' . esc_attr( $price_max ) . '"><span class="move"></span><span class="qty hint--right" aria-label="' . esc_html__( 'Default quantity', 'woo-product-bundle' ) . '">' . $qty_input . '</span> <span class="data">' . ( $product->get_status() === 'private' ? '<span class="info">private</span> ' : '' ) . '<span class="name">' . strip_tags( $product_name ) . '</span> <span class="info">' . $product->get_price_html() . '</span> ' . ( $product->is_sold_individually() ? '<span class="info">' . esc_html__( 'sold individually', 'woo-product-bundle' ) . '</span> ' : '' ) . '</span> <span class="type"><a href="' . $edit_link . '" target="_blank">' . $product_info . '</a></span> ' . $remove_btn . '</li>';
		}

		function process_product_meta( $post_id ) {
			if ( isset( $_POST['product-type'] ) && ( $_POST['product-type'] !== 'woosb' ) ) {
				delete_post_meta( $post_id, 'woosb_ids' );
			}
		}

		function process_product_meta_woosb( $post_id ) {
			if ( ! empty( $_POST['woosb_ids'] ) ) {
				update_post_meta( $post_id, 'woosb_ids', WPCleverWoosb_Helper::clean_ids( $_POST['woosb_ids'] ) );
			}

			if ( isset( $_POST['woosb_disable_auto_price'] ) ) {
				update_post_meta( $post_id, 'woosb_disable_auto_price', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_disable_auto_price', 'off' );
			}

			if ( isset( $_POST['woosb_discount'] ) ) {
				update_post_meta( $post_id, 'woosb_discount', sanitize_text_field( $_POST['woosb_discount'] ) );
			} else {
				update_post_meta( $post_id, 'woosb_discount', 0 );
			}

			if ( isset( $_POST['woosb_discount_amount'] ) ) {
				update_post_meta( $post_id, 'woosb_discount_amount', sanitize_text_field( $_POST['woosb_discount_amount'] ) );
			} else {
				update_post_meta( $post_id, 'woosb_discount_amount', 0 );
			}

			if ( isset( $_POST['woosb_shipping_fee'] ) ) {
				update_post_meta( $post_id, 'woosb_shipping_fee', sanitize_text_field( $_POST['woosb_shipping_fee'] ) );
			}

			if ( isset( $_POST['woosb_optional_products'] ) ) {
				update_post_meta( $post_id, 'woosb_optional_products', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_optional_products', 'off' );
			}

			if ( isset( $_POST['woosb_manage_stock'] ) ) {
				update_post_meta( $post_id, 'woosb_manage_stock', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_manage_stock', 'off' );
			}

			if ( isset( $_POST['woosb_custom_price'] ) && ( $_POST['woosb_custom_price'] !== '' ) ) {
				update_post_meta( $post_id, 'woosb_custom_price', addslashes( $_POST['woosb_custom_price'] ) );
			} else {
				delete_post_meta( $post_id, 'woosb_custom_price' );
			}

			if ( isset( $_POST['woosb_limit_each_min'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_each_min', sanitize_text_field( $_POST['woosb_limit_each_min'] ) );
			}

			if ( isset( $_POST['woosb_limit_each_max'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_each_max', sanitize_text_field( $_POST['woosb_limit_each_max'] ) );
			}

			if ( isset( $_POST['woosb_limit_each_min_default'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_each_min_default', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_limit_each_min_default', 'off' );
			}

			if ( isset( $_POST['woosb_limit_whole_min'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_whole_min', sanitize_text_field( $_POST['woosb_limit_whole_min'] ) );
			}

			if ( isset( $_POST['woosb_limit_whole_max'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_whole_max', sanitize_text_field( $_POST['woosb_limit_whole_max'] ) );
			}

			if ( isset( $_POST['woosb_total_limits'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_total_limits', 'off' );
			}

			if ( isset( $_POST['woosb_total_limits_min'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits_min', sanitize_text_field( $_POST['woosb_total_limits_min'] ) );
			}

			if ( isset( $_POST['woosb_total_limits_max'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits_max', sanitize_text_field( $_POST['woosb_total_limits_max'] ) );
			}

			if ( isset( $_POST['woosb_before_text'] ) && ( $_POST['woosb_before_text'] !== '' ) ) {
				update_post_meta( $post_id, 'woosb_before_text', addslashes( $_POST['woosb_before_text'] ) );
			} else {
				delete_post_meta( $post_id, 'woosb_before_text' );
			}

			if ( isset( $_POST['woosb_after_text'] ) && ( $_POST['woosb_after_text'] !== '' ) ) {
				update_post_meta( $post_id, 'woosb_after_text', addslashes( $_POST['woosb_after_text'] ) );
			} else {
				delete_post_meta( $post_id, 'woosb_after_text' );
			}
		}

		function product_price_class( $class ) {
			global $product;

			if ( $product && $product->is_type( 'woosb' ) ) {
				$class .= ' woosb-price-' . $product->get_id();
			}

			return $class;
		}

		function add_to_cart_form() {
			global $product;

			if ( ! $product || ! $product->is_type( 'woosb' ) ) {
				return;
			}

			if ( $product->has_variables() ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );
			}

			if ( ( WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' ) === 'above' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				self::show_bundled();
			}

			wc_get_template( 'single-product/add-to-cart/simple.php' );

			if ( ( WPCleverWoosb_Helper::get_setting( 'bundled_position', 'above' ) === 'below' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				self::show_bundled();
			}
		}

		function add_to_cart_button() {
			global $product;

			if ( $product->is_type( 'woosb' ) && ( $ids = $product->get_ids() ) ) {
				echo '<input name="woosb_ids" class="woosb-ids woosb-ids-' . esc_attr( $product->get_id() ) . '" type="hidden" value="' . esc_attr( $ids ) . '"/>';
			}
		}

		function loop_add_to_cart_link( $link, $product ) {
			if ( $product->is_type( 'woosb' ) && ( $product->has_variables() || $product->is_optional() ) ) {
				$link = str_replace( 'ajax_add_to_cart', '', $link );
			}

			return $link;
		}

		function cart_shipping_packages( $packages ) {
			if ( ! empty( $packages ) ) {
				foreach ( $packages as $package_key => $package ) {
					if ( ! empty( $package['contents'] ) ) {
						foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
							if ( ! empty( $cart_item['woosb_parent_id'] ) && ( get_post_meta( $cart_item['woosb_parent_id'], 'woosb_shipping_fee', true ) !== 'each' ) ) {
								unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
							}

							if ( ! empty( $cart_item['woosb_ids'] ) && ( get_post_meta( $cart_item['data']->get_id(), 'woosb_shipping_fee', true ) === 'each' ) ) {
								unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
							}
						}
					}
				}
			}

			return $packages;
		}

		function cart_contents_weight( $weight ) {
			$weight = 0;

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( $cart_item['data']->has_weight() ) {
					if ( ( ! empty( $cart_item['woosb_parent_id'] ) && ( get_post_meta( $cart_item['woosb_parent_id'], 'woosb_shipping_fee', true ) !== 'each' ) ) || ( ! empty( $cart_item['woosb_ids'] ) && ( get_post_meta( $cart_item['data']->get_id(), 'woosb_shipping_fee', true ) === 'each' ) ) ) {
						$weight += 0;
					} else {
						$weight += (float) $cart_item['data']->get_weight() * $cart_item['quantity'];
					}
				}
			}

			return $weight;
		}

		function get_price_html( $price, $product ) {
			if ( $product->is_type( 'woosb' ) && ( $items = $product->get_items() ) ) {
				$product_id           = $product->get_id();
				$custom_price         = stripslashes( get_post_meta( $product_id, 'woosb_custom_price', true ) );
				$price_format         = WPCleverWoosb_Helper::get_setting( 'price_format', 'from_min' );
				$global_custom_price  = $price_format === 'custom';
				$default_custom_price = stripslashes( WPCleverWoosb_Helper::get_setting( 'price_format_custom', esc_html__( 'before %s after', 'woo-product-bundle' ) ) );

				if ( ! $product->is_fixed_price() ) {
					$discount_amount     = $product->get_discount_amount();
					$discount_percentage = $product->get_discount_percentage();

					if ( $product->is_optional() ) {
						if ( $price_format === 'min_only' || $price_format === 'from_min' ) {
							foreach ( $items as $k => $item ) {
								$_product = wc_get_product( $item['id'] );

								if ( $_product ) {
									$items[ $k ]['price'] = WPCleverWoosb_Helper::get_price_to_display( $_product );
								} else {
									$items[ $k ]['price'] = 0;
								}
							}

							// min price
							$min_price = min( array_column( $items, 'price' ) );

							// min each
							$min_each_default = get_post_meta( $product_id, 'woosb_limit_each_min_default', true ) === 'on';
							$min_each         = (float) ( get_post_meta( $product_id, 'woosb_limit_each_min', true ) ?: 0 );
							$total_qty        = 0;

							if ( $min_each_default ) {
								$min_price = 0;

								foreach ( $items as $item ) {
									$min_price += (float) $item['price'] * (float) $item['qty'];
									$total_qty += (float) $item['qty'];
								}
							} elseif ( $min_each > 0 ) {
								$min_price = 0;

								foreach ( $items as $item ) {
									$min_price += (float) $item['price'] * $min_each;
									$total_qty += $min_each;
								}
							}

							// min whole
							$min_whole = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 1 );

							if ( $total_qty > 0 ) {
								// has min each
								if ( $min_whole > $total_qty ) {
									$min_price += ( $min_whole - $total_qty ) * min( array_column( $items, 'price' ) );
								}
							} else {
								$min_price *= $min_whole;
							}

							// discount
							if ( $discount_amount ) {
								$min_price -= (float) $discount_amount;
							} elseif ( $discount_percentage ) {
								$min_price *= (float) ( 100 - $discount_percentage ) / 100;
							}

							switch ( $price_format ) {
								case 'min_only':
									$price = wc_price( $min_price ) . $product->get_price_suffix();
									break;
								case 'from_min':
									$price = esc_html__( 'From', 'woo-product-bundle' ) . ' ' . wc_price( $min_price ) . $product->get_price_suffix();
									break;
							}
						}
					} elseif ( $product->has_variables() ) {
						if ( $price_format === 'min_only' || $price_format === 'min_max' || $price_format === 'from_min' ) {
							$min_price = $max_price = 0;

							foreach ( $items as $item ) {
								$_product = wc_get_product( $item['id'] );

								if ( $_product ) {
									$min_price += WPCleverWoosb_Helper::get_price_to_display( $_product, $item['qty'] );
									$max_price += WPCleverWoosb_Helper::get_price_to_display( $_product, $item['qty'], 'max' );
								}
							}

							if ( $discount_amount ) {
								$min_price -= (float) $discount_amount;
								$max_price -= (float) $discount_amount;
							} elseif ( $discount_percentage ) {
								$min_price *= (float) ( 100 - $discount_percentage ) / 100;
								$max_price *= (float) ( 100 - $discount_percentage ) / 100;
							}

							switch ( $price_format ) {
								case 'min_only':
									$price = wc_price( $min_price ) . $product->get_price_suffix();
									break;
								case 'min_max':
									$price = wc_price( $min_price ) . ' - ' . wc_price( $max_price ) . $product->get_price_suffix();
									break;
								case 'from_min':
									$price = esc_html__( 'From', 'woo-product-bundle' ) . ' ' . wc_price( $min_price ) . $product->get_price_suffix();
									break;
							}
						}
					} else {
						// auto calculated price
						$price_regular = $price_sale = 0;

						foreach ( $items as $item ) {
							$_product = wc_get_product( $item['id'] );

							if ( $_product ) {
								$_price = WPCleverWoosb_Helper::get_price_to_display( $_product, $item['qty'] );

								$price_regular += $_price;

								if ( $discount_percentage ) {
									// when haven't discount_amount, apply the discount percentage
									$_price     *= ( 100 - (float) $discount_percentage ) / 100;
									$_price     = round( $_price, wc_get_price_decimals() );
									$price_sale += apply_filters( 'woosb_item_price_add_to_cart', $_price, $item );
								}
							}
						}

						if ( $discount_amount ) {
							$price_sale = $price_regular - $discount_amount;
						}

						if ( $price_sale ) {
							$price = wc_format_sale_price( wc_price( $price_regular ), wc_price( $price_sale ) ) . $product->get_price_suffix();
						} else {
							$price = wc_price( $price_regular ) . $product->get_price_suffix();
						}
					}
				}

				if ( ! empty( $custom_price ) ) {
					return str_replace( '%s', $price, $custom_price );
				}

				if ( $global_custom_price ) {
					return str_replace( '%s', $price, $default_custom_price );
				}
			}

			return $price;
		}

		function order_again_cart_item_data( $data, $item ) {
			if ( $ids = $item->get_meta( '_woosb_ids' ) ) {
				$data['woosb_order_again'] = 'yes';
				$data['woosb_ids']         = $ids;
			}

			if ( $parent_id = $item->get_meta( '_woosb_parent_id' ) ) {
				$data['woosb_order_again'] = 'yes';
				$data['woosb_parent_id']   = $parent_id;
			}

			return $data;
		}

		function cart_loaded_from_session() {
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				// remove bundled products first
				if ( isset( $cart_item['woosb_order_again'], $cart_item['woosb_parent_id'] ) ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				// add bundled products again
				if ( isset( $cart_item['woosb_order_again'], $cart_item['woosb_ids'] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_order_again'] );
					$cart_item['data']->build_items( $cart_item['woosb_ids'] );
					$items = $cart_item['data']->get_items();
					self::add_to_cart_items( $items, $cart_item_key, $cart_item['product_id'], $cart_item['quantity'] );
				}
			}
		}

		function coupon_is_valid_for_product( $valid, $product, $coupon, $cart_item ) {
			if ( ( WPCleverWoosb_Helper::get_setting( 'coupon_restrictions', 'no' ) === 'both' ) && ( isset( $cart_item['woosb_parent_id'] ) || isset( $cart_item['woosb_ids'] ) ) ) {
				// exclude both bundles and bundled products
				return false;
			}

			if ( ( WPCleverWoosb_Helper::get_setting( 'coupon_restrictions', 'no' ) === 'bundles' ) && isset( $cart_item['woosb_ids'] ) ) {
				// exclude bundles
				return false;
			}

			if ( ( WPCleverWoosb_Helper::get_setting( 'coupon_restrictions', 'no' ) === 'bundled' ) && isset( $cart_item['woosb_parent_id'] ) ) {
				// exclude bundled products
				return false;
			}

			if ( isset( $cart_item['woosb_parent_id'] ) && ( $parent = wc_get_product( $cart_item['woosb_parent_id'] ) ) ) {
				return $coupon->is_valid_for_product( $parent );
			}

			return $valid;
		}

		function show_bundled( $product = null ) {
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

			if ( $items = $product->get_items() ) {
				$total_limit     = get_post_meta( $product_id, 'woosb_total_limits', true ) === 'on';
				$total_limit_min = get_post_meta( $product_id, 'woosb_total_limits_min', true );
				$total_limit_max = get_post_meta( $product_id, 'woosb_total_limits_max', true );

				do_action( 'woosb_before_wrap', $product );

				echo '<div class="woosb-wrap woosb-bundled" data-id="' . esc_attr( $product_id ) . '">';

				if ( $before_text = apply_filters( 'woosb_before_text', get_post_meta( $product_id, 'woosb_before_text', true ), $product_id ) ) {
					echo '<div class="woosb-before-text woosb-text">' . do_shortcode( stripslashes( $before_text ) ) . '</div>';
				}

				do_action( 'woosb_before_table', $product );
				?>
                <div class="woosb-products"
                     data-discount-amount="<?php echo esc_attr( $discount_amount ); ?>"
                     data-discount="<?php echo esc_attr( $discount_percentage ); ?>"
                     data-fixed-price="<?php echo esc_attr( $fixed_price ? 'yes' : 'no' ); ?>"
                     data-price="<?php echo esc_attr( wc_get_price_to_display( $product ) ); ?>"
                     data-price-suffix="<?php echo esc_attr( htmlentities( $product->get_price_suffix() ) ); ?>"
                     data-variables="<?php echo esc_attr( $product->has_variables() ? 'yes' : 'no' ); ?>"
                     data-optional="<?php echo esc_attr( $product->is_optional() ? 'yes' : 'no' ); ?>"
                     data-min="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 0 ); ?>"
                     data-max="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ?: '-1' ); ?>"
                     data-total-min="<?php echo esc_attr( $total_limit && $total_limit_min ? $total_limit_min : 0 ); ?>"
                     data-total-max="<?php echo esc_attr( $total_limit && $total_limit_max ? $total_limit_max : '-1' ); ?>">
					<?php
					foreach ( $items as $item ) {
						$_product = wc_get_product( $item['id'] );
						$_qty     = $item['qty'];

						if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) ) {
							continue;
						}

						if ( ! apply_filters( 'woosb_item_exclude', true, $_product, $product ) ) {
							continue;
						}

						if ( $product->is_optional() ) {
							if ( get_post_meta( $product_id, 'woosb_limit_each_min_default', true ) === 'on' ) {
								$_min = $_qty;
							} else {
								$_min = absint( get_post_meta( $product_id, 'woosb_limit_each_min', true ) ?: 0 );
							}

							$_max = absint( get_post_meta( $product_id, 'woosb_limit_each_max', true ) ?: 10000 );

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

						$item_class = 'woosb-product';

						if ( ! apply_filters( 'woosb_item_visible', true, $_product, $product ) ) {
							$item_class .= ' woosb-product-hidden';
						}

						if ( ( ! $_product->is_in_stock() || ! $_product->has_enough_stock( $_qty ) || ! $_product->is_purchasable() ) && ( WPCleverWoosb_Helper::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) ) {
							$_qty       = 0;
							$item_class .= ' woosb-product-unpurchasable';
						}

						do_action( 'woosb_above_item', $_product, $product, $order );
						?>
                        <div class="<?php echo esc_attr( apply_filters( 'woosb_item_class', $item_class, $_product, $product, $order ) ); ?>"
                             data-name="<?php echo esc_attr( $_product->get_name() ); ?>"
                             data-id="<?php echo esc_attr( $_product->is_type( 'variable' ) ? 0 : $item['id'] ); ?>"
                             data-price="<?php echo esc_attr( WPCleverWoosb_Helper::get_price_to_display( $_product ) ); ?>"
                             data-price-suffix="<?php echo esc_attr( htmlentities( $_product->get_price_suffix() ) ); ?>"
                             data-qty="<?php echo esc_attr( $_qty ); ?>" data-order="<?php echo esc_attr( $order ); ?>">
							<?php
							do_action( 'woosb_before_item', $_product, $product, $order );

							if ( WPCleverWoosb_Helper::get_setting( 'bundled_thumb', 'yes' ) !== 'no' ) { ?>
                                <div class="woosb-thumb">
									<?php if ( $_product->is_visible() && ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
										echo '<a ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . $item['id'] . '" data-context="woosb"' : '' ) . ' href="' . esc_url( $_product->get_permalink() ) . '" ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
									} ?>
                                    <div class="woosb-thumb-ori">
										<?php echo apply_filters( 'woosb_item_thumbnail', $_product->get_image( self::$image_size ), $_product ); ?>
                                    </div>
                                    <div class="woosb-thumb-new"></div>
									<?php if ( $_product->is_visible() && ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
										echo '</a>';
									} ?>
                                </div>
							<?php } ?>

                            <div class="woosb-title">
								<?php
								do_action( 'woosb_before_item_name', $_product );

								echo '<div class="woosb-title-inner">';

								if ( ( WPCleverWoosb_Helper::get_setting( 'bundled_qty', 'yes' ) === 'yes' ) && ( get_post_meta( $product_id, 'woosb_optional_products', true ) !== 'on' ) ) {
									echo apply_filters( 'woosb_item_qty', $item['qty'] . ' × ', $item['qty'], $_product );
								}

								$_item_name    = '';
								$_product_name = apply_filters( 'woosb_item_product_name', $_product->get_name(), $_product );

								if ( $_product->is_visible() && ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
									$_item_name .= '<a ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . $item['id'] . '" data-context="woosb"' : '' ) . ' href="' . esc_url( $_product->get_permalink() ) . '" ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
								}

								if ( $_product->is_in_stock() && $_product->has_enough_stock( $_qty ) ) {
									$_item_name .= $_product_name;
								} else {
									$_item_name .= '<s>' . $_product_name . '</s>';
								}

								if ( $_product->is_visible() && ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
									$_item_name .= '</a>';
								}

								echo apply_filters( 'woosb_item_name', $_item_name, $_product, $product, $order );
								echo '</div>';

								do_action( 'woosb_after_item_name', $_product );

								if ( WPCleverWoosb_Helper::get_setting( 'bundled_description', 'no' ) === 'yes' ) {
									echo '<div class="woosb-description">' . apply_filters( 'woosb_item_description', $_product->get_short_description(), $_product ) . '</div>';
								}

								if ( $_product->is_type( 'variable' ) ) {
									if ( ( WPCleverWoosb_Helper::get_setting( 'variations_selector', 'default' ) === 'wpc_radio' || WPCleverWoosb_Helper::get_setting( 'variations_selector', 'default' ) === 'woovr' ) && class_exists( 'WPClever_Woovr' ) ) {
										WPClever_Woovr::woovr_variations_form( $_product );
									} else {
										$attributes           = $_product->get_variation_attributes();
										$available_variations = $_product->get_available_variations();
										$variations_json      = wp_json_encode( $available_variations );
										$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

										if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {
											echo '<div class="variations_form" data-product_id="' . absint( $_product->get_id() ) . '" data-product_variations="' . $variations_attr . '">';
											echo '<div class="variations">';

											foreach ( $attributes as $attribute_name => $options ) { ?>
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
															'show_option_none' => sprintf( WPCleverWoosb_Helper::localization( 'choose', esc_html__( 'Choose %s', 'woo-product-bundle' ) ), wc_attribute_label( $attribute_name ) )
														) );
														?>
                                                    </div>
                                                </div>
											<?php }

											echo '<div class="reset">' . apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . WPCleverWoosb_Helper::localization( 'clear', esc_html__( 'Clear', 'woo-product-bundle' ) ) . '</a>' ) . '</div>';
											echo '</div>';
											echo '</div>';

											if ( WPCleverWoosb_Helper::get_setting( 'bundled_description', 'no' ) === 'yes' ) {
												echo '<div class="woosb-variation-description"></div>';
											}
										}
									}

									do_action( 'woosb_after_item_variations', $_product );
								}

								echo '<div class="woosb-availability">' . wc_get_stock_html( $_product ) . '</div>';
								?>
                            </div>

							<?php if ( $product->is_optional() ) {
								if ( $_product->is_in_stock() && $_product->is_purchasable() ) {
									echo '<div class="' . esc_attr( WPCleverWoosb_Helper::get_setting( 'plus_minus', 'no' ) === 'yes' ? 'woosb-quantity woosb-quantity-plus-minus' : 'woosb-quantity' ) . '">';

									if ( WPCleverWoosb_Helper::get_setting( 'plus_minus', 'no' ) === 'yes' ) {
										echo '<div class="woosb-quantity-input">';
										echo '<div class="woosb-quantity-input-minus">-</div>';
									}

									woocommerce_quantity_input( array(
										'input_value' => $_qty,
										'min_value'   => $_min,
										'max_value'   => $_max,
										'woosb_qty'   => array(
											'input_value' => $_qty,
											'min_value'   => $_min,
											'max_value'   => $_max
										),
										'classes'     => apply_filters( 'woosb_qty_classes', array(
											'input-text',
											'woosb-qty',
											'qty',
											'text'
										) ),
										'input_name'  => 'woosb_qty_' . $order // compatible with WPC Product Quantity
									), $_product );

									if ( WPCleverWoosb_Helper::get_setting( 'plus_minus', 'no' ) === 'yes' ) {
										echo '<div class="woosb-quantity-input-plus">+</div>';
										echo '</div>';
									}

									echo '</div>';
								} else { ?>
                                    <div class="woosb-quantity woosb-quantity-disabled">
                                        <input type="number" class="input-text qty text" value="0" disabled/>
                                    </div>
								<?php }
							}

							if ( ( $bundled_price = WPCleverWoosb_Helper::get_setting( 'bundled_price', 'price' ) ) !== 'no' ) { ?>
                                <div class="woosb-price">
                                    <div class="woosb-price-ori">
										<?php
										$_ori_price = $_product->get_price();
										$_get_price = WPCleverWoosb_Helper::get_price( $_product );

										if ( ! $product->is_fixed_price() && ( $discount_percentage = $product->get_discount_percentage() ) ) {
											$_new_price     = true;
											$_product_price = $_get_price * ( 100 - (float) $discount_percentage ) / 100;
											$_product_price = round( $_product_price, wc_get_price_decimals() );
											$_product_price = apply_filters( 'woosb_item_price_add_to_cart', $_product_price, $item );
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
														$_price = wc_price( WPCleverWoosb_Helper::get_price_to_display( $_product ) ) . $_product->get_price_suffix();
													} else {
														$_price = $_product->get_price_html();
													}
												}

												break;
											case 'subtotal':
												if ( $_new_price ) {
													$_price = wc_format_sale_price( wc_get_price_to_display( $_product, array(
															'price' => $_get_price,
															'qty'   => $item['qty']
														) ), wc_get_price_to_display( $_product, array(
															'price' => $_product_price,
															'qty'   => $item['qty']
														) ) ) . $_product->get_price_suffix();
												} else {
													$_price = wc_price( WPCleverWoosb_Helper::get_price_to_display( $_product, $item['qty'] ) ) . $_product->get_price_suffix();
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
							<?php }

							do_action( 'woosb_after_item', $_product, $product, $order );
							?>
                        </div>
						<?php
						do_action( 'woosb_under_item', $_product, $product, $order );

						$order ++;
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

		function show_bundles( $product = null ) {
			if ( ! $product ) {
				global $product;
			}

			if ( ! $product || $product->is_type( 'woosb' ) ) {
				return;
			}

			$product_id = $product->get_id();
			$bundles    = self::get_bundles( $product_id ) ?: array();

			if ( $product->is_type( 'variable' ) ) {
				$children = $product->get_children();

				if ( is_array( $children ) && count( $children ) > 0 ) {
					foreach ( $children as $child ) {
						if ( $child_bundles = self::get_bundles( $child ) ) {
							foreach ( $child_bundles as $child_bundle ) {
								$bundles[] = $child_bundle;
							}
						}
					}
				}
			}

			if ( ! empty( $bundles ) ) {
				echo '<div class="woosb-bundles">';

				if ( $before_text = apply_filters( 'woosb_bundles_before_text', WPCleverWoosb_Helper::get_setting( 'bundles_before_text' ) ) ) {
					echo '<div class="woosb_before_text woosb-before-text woosb-text">' . do_shortcode( stripslashes( $before_text ) ) . '</div>';
				}

				do_action( 'woosb_before_bundles', $product );
				echo '<div class="woosb-products">';

				foreach ( array_unique( $bundles ) as $bundle ) {
					echo '<div class="woosb-product">';
					echo '<div class="woosb-thumb">' . $bundle->get_image( self::$image_size ) . '</div>';
					echo '<div class="woosb-title"><a ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . $bundle->get_id() . '" data-context="woosb"' : '' ) . ' href="' . $bundle->get_permalink() . '" ' . ( WPCleverWoosb_Helper::get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . $bundle->get_name() . '</a></div>';
					echo '<div class="woosb-price">' . $bundle->get_price_html() . '</div>';
					echo '</div><!-- /woosb-product -->';
				}

				echo '</div><!-- /woosb-products -->';
				wp_reset_postdata();

				if ( $after_text = apply_filters( 'woosb_bundles_after_text', WPCleverWoosb_Helper::get_setting( 'bundles_after_text' ) ) ) {
					echo '<div class="woosb_after_text woosb-after-text woosb-text">' . do_shortcode( stripslashes( $after_text ) ) . '</div>';
				}

				do_action( 'woosb_after_bundles', $product );
				echo '</div><!-- /woosb-bundles -->';
			}
		}

		function get_bundled( $ids ) {
			$bundled = array();

			if ( ! empty( $ids ) ) {
				$items = explode( ',', $ids );

				if ( is_array( $items ) && count( $items ) > 0 ) {
					foreach ( $items as $item ) {
						$_arr = explode( '/', $item );
						$_id  = rawurldecode( isset( $_arr[0] ) ? $_arr[0] : 0 );

						if ( ! is_numeric( $_id ) ) {
							// sku
							$_id = wc_get_product_id_by_sku( ltrim( $_id, 'sku-' ) );
						}

						$_qty = (float) ( isset( $_arr[1] ) ? $_arr[1] : 1 );

						$bundled[] = array(
							'id'  => $_id,
							'qty' => $_qty
						);
					}
				}
			}

			return $bundled;
		}

		function get_bundles( $product_id, $per_page = 500, $offset = 0 ) {
			$bundles = array();
			$id_str  = $product_id . '/';
			$sku     = get_post_meta( $product_id, '_sku', true );

			if ( ! empty( $sku ) ) {
				$sku_str = $sku . '/';
			} else {
				$sku_str = 'woosb/';
			}

			$query_args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'offset'         => $offset,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( 'woosb' ),
						'operator' => 'IN',
					)
				),
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'woosb_ids',
						'value'   => ',' . $id_str,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'woosb_ids',
						'value'   => '^' . $id_str,
						'compare' => 'REGEXP',
					),
					array(
						'key'     => 'woosb_ids',
						'value'   => ',' . $sku_str,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'woosb_ids',
						'value'   => '^' . $sku_str,
						'compare' => 'REGEXP',
					)
				)
			);
			$query      = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$_product = wc_get_product( get_the_ID() );

					if ( ! $_product ) {
						continue;
					}

					$bundles[] = $_product;
				}

				wp_reset_query();
			}

			return ! empty( $bundles ) ? $bundles : false;
		}

		function shortcode_form() {
			ob_start();
			self::add_to_cart_form();

			return ob_get_clean();
		}

		function shortcode_bundled() {
			ob_start();
			self::show_bundled();

			return ob_get_clean();
		}

		function shortcode_bundles() {
			ob_start();
			self::show_bundles();

			return ob_get_clean();
		}

		function display_post_states( $states, $post ) {
			if ( 'product' == get_post_type( $post->ID ) ) {
				if ( ( $_product = wc_get_product( $post->ID ) ) && $_product->is_type( 'woosb' ) ) {
					$count = 0;

					if ( $items = $_product->get_items() ) {
						$count = count( $items );
					}

					$states[] = apply_filters( 'woosb_post_states', '<span class="woosb-state">' . sprintf( esc_html__( 'Bundle (%s)', 'woo-product-bundle' ), $count ) . '</span>', $count, $_product );
				}
			}

			return $states;
		}

		function bulk_actions() {
			if ( current_user_can( 'edit_products' ) ) {
				add_filter( 'bulk_actions-edit-product', array( $this, 'bulk_actions_register' ) );
				add_filter( 'handle_bulk_actions-edit-product', array( $this, 'bulk_actions_handler' ), 10, 3 );
				add_action( 'admin_notices', array( $this, 'bulk_actions_notice' ) );
			}
		}

		function bulk_actions_register( $bulk_actions ) {
			$bulk_actions['woosb_create_bundle'] = esc_html__( 'Create a Smart bundle', 'woo-product-bundle' );

			return $bulk_actions;
		}

		function bulk_actions_handler( $redirect_to, $do_action, $post_ids ) {
			if ( $do_action !== 'woosb_create_bundle' ) {
				return $redirect_to;
			}

			$ids = implode( '.', $post_ids );

			return add_query_arg( 'woosb_ids', $ids, admin_url( 'post-new.php?post_type=product' ) );
		}

		function bulk_actions_notice() {
			if ( ! empty( $_REQUEST['woosb_ids'] ) ) {
				$ids = explode( '.', $_REQUEST['woosb_ids'] );
				echo '<div id="message" class="updated fade">' . sprintf( esc_html__( 'Added %s product(s) to this bundle.', 'woo-product-bundle' ), count( $ids ) ) . '</div>';
			}
		}

		function no_stock_notification( $product ) {
			if ( 'no' === get_option( 'woocommerce_notify_no_stock', 'yes' ) ) {
				return;
			}

			$message    = '';
			$subject    = sprintf( '[%s] %s', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html__( 'Bundle(s) out of stock', 'woo-product-bundle' ) );
			$product_id = $product->get_id();

			if ( $bundles = self::get_bundles( $product_id ) ) {
				foreach ( $bundles as $bundle ) {
					$message .= sprintf( esc_html__( '%s is out of stock.', 'woo-product-bundle' ), html_entity_decode( strip_tags( $bundle->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $bundle->get_id() ) . '" target="_blank">#' . $bundle->get_id() . '</a><br/>';
				}

				$message .= sprintf( esc_html__( '%s is out of stock.', 'woo-product-bundle' ), html_entity_decode( strip_tags( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $product_id ) . '" target="_blank">#' . $product_id . '</a>';

				wp_mail(
					apply_filters( 'woocommerce_email_recipient_no_stock', get_option( 'woocommerce_stock_email_recipient' ), $product, null ),
					apply_filters( 'woocommerce_email_subject_no_stock', $subject, $product, null ),
					apply_filters( 'woocommerce_email_content_no_stock', $message, $product ),
					apply_filters( 'woocommerce_email_headers', 'Content-Type: text/html; charset=UTF-8', 'no_stock', $product, null ),
					apply_filters( 'woocommerce_email_attachments', array(), 'no_stock', $product, null )
				);
			}
		}

		function low_stock_notification( $product ) {
			if ( 'no' === get_option( 'woocommerce_notify_low_stock', 'yes' ) ) {
				return;
			}

			$message = '';
			$subject = sprintf( '[%s] %s', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html__( 'Bundle(s) low in stock', 'woo-product-bundle' ) );

			$product_id = $product->get_id();
			if ( $bundles = self::get_bundles( $product_id ) ) {
				foreach ( $bundles as $bundle ) {
					$message .= sprintf( esc_html__( '%s is low in stock.', 'woo-product-bundle' ), html_entity_decode( strip_tags( $bundle->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $bundle->get_id() ) . '" target="_blank">#' . $bundle->get_id() . '</a><br/>';
				}

				$message .= sprintf( esc_html__( '%1$s is low in stock. There are %2$d left.', 'woo-product-bundle' ), html_entity_decode( strip_tags( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ), html_entity_decode( strip_tags( $product->get_stock_quantity() ) ) ) . ' <a href="' . get_edit_post_link( $product_id ) . '" target="_blank">#' . $product_id . '</a>';

				wp_mail(
					apply_filters( 'woocommerce_email_recipient_low_stock', get_option( 'woocommerce_stock_email_recipient' ), $product, null ),
					apply_filters( 'woocommerce_email_subject_low_stock', $subject, $product, null ),
					apply_filters( 'woocommerce_email_content_low_stock', $message, $product ),
					apply_filters( 'woocommerce_email_headers', 'Content-Type: text/html; charset=UTF-8', 'low_stock', $product, null ),
					apply_filters( 'woocommerce_email_attachments', array(), 'low_stock', $product, null )
				);
			}
		}

		function wpml_item_id( $id ) {
			return apply_filters( 'wpml_object_id', $id, 'product', true );
		}
	}
}

function WPCleverWoosb() {
	return WPCleverWoosb::instance();
}